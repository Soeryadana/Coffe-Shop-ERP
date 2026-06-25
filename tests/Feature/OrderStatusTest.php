<?php
// tests/Feature/OrderStatusTest.php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected ProductVariant $variant;
    protected Ingredient $coffeeBeans;
    protected Ingredient $milk;
    protected DiningTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password123'),
            'role' => 'cashier',
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $category = Category::create(['name' => 'Coffee']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cappuccino',
            'is_active' => true,
        ]);

        $this->variant = $product->variants()->create([
            'name' => 'Hot - Regular',
            'price' => 22000,
        ]);

        $this->coffeeBeans = Ingredient::create([
            'name' => 'Coffee Beans',
            'unit' => 'g',
            'stock_quantity' => 1000,
            'min_stock_alert' => 100,
        ]);

        $this->milk = Ingredient::create([
            'name' => 'Fresh Milk',
            'unit' => 'ml',
            'stock_quantity' => 2000,
            'min_stock_alert' => 200,
        ]);

        // Recipe: 1 Cappuccino = 18g coffee beans + 150ml milk
        ProductRecipe::create([
            'product_variant_id' => $this->variant->id,
            'ingredient_id' => $this->coffeeBeans->id,
            'quantity_used' => 18,
        ]);

        ProductRecipe::create([
            'product_variant_id' => $this->variant->id,
            'ingredient_id' => $this->milk->id,
            'quantity_used' => 150,
        ]);

        $this->table = DiningTable::create([
            'table_number' => 'T01',
            'status' => 'available',
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function createOrder(int $quantity = 1, string $orderType = 'takeaway', ?int $tableId = null): Order
    {
        $payload = [
            'order_type' => $orderType,
            'items' => [
                ['product_variant_id' => $this->variant->id, 'quantity' => $quantity],
            ],
        ];

        if ($orderType === 'dine_in') {
            $payload['table_id'] = $tableId ?? $this->table->id;
        }

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', $payload);

        return Order::find($response->json('data.id'));
    }

    public function test_completing_order_deducts_ingredient_stock(): void
    {
        $order = $this->createOrder(quantity: 2); // 2 cappuccinos

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        // 2 cappuccinos = 36g beans, 300ml milk used
        $this->assertEquals(964, $this->coffeeBeans->fresh()->stock_quantity);
        $this->assertEquals(1700, $this->milk->fresh()->stock_quantity);
    }

    public function test_completing_order_creates_stock_movement_records(): void
    {
        $order = $this->createOrder(quantity: 1);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'completed',
            ]);

        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'out',
            'quantity' => 18,
            'reason' => "Order #{$order->order_number}",
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $this->milk->id,
            'type' => 'out',
            'quantity' => 150,
        ]);
    }

    public function test_completing_order_with_insufficient_stock_rolls_back_status(): void
    {
        // Order way more cappuccinos than the milk supply can support
        // 2000ml milk / 150ml per cup = max 13 cups before running out
        $order = $this->createOrder(quantity: 20); // needs 3000ml milk, only have 2000ml

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(422);

        // Status should have rolled back to pending, NOT completed
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);

        // Stock should be untouched since the transaction rolled back
        $this->assertEquals(1000, $this->coffeeBeans->fresh()->stock_quantity);
        $this->assertEquals(2000, $this->milk->fresh()->stock_quantity);
    }

    public function test_completing_order_does_not_deduct_stock_twice(): void
    {
        $order = $this->createOrder(quantity: 1);

        // Complete it once
        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'completed']);

        // Try "completing" it again (e.g. duplicate request)
        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'completed']);

        // Stock should only have been deducted ONCE (18g, not 36g)
        $this->assertEquals(982, $this->coffeeBeans->fresh()->stock_quantity);
    }

    public function test_cancelling_dine_in_order_releases_table(): void
    {
        $order = $this->createOrder(orderType: 'dine_in');

        $this->assertDatabaseHas('dining_tables', [
            'id' => $this->table->id,
            'status' => 'occupied',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('dining_tables', [
            'id' => $this->table->id,
            'status' => 'available',
        ]);
    }

    public function test_completing_dine_in_order_does_not_release_table(): void
    {
        $order = $this->createOrder(orderType: 'dine_in');

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'completed',
            ]);

        // Table should STILL be occupied — staff must manually release it
        $this->assertDatabaseHas('dining_tables', [
            'id' => $this->table->id,
            'status' => 'occupied',
        ]);
    }

    public function test_status_update_rejects_invalid_status(): void
    {
        $order = $this->createOrder();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'served', // not a valid enum value
            ]);

        $response->assertStatus(422);
    }
}