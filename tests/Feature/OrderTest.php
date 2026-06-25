<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected ProductVariant $variant;
    protected ProductVariant $variant2;
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

        $this->variant2 = $product->variants()->create([
            'name' => 'Iced - Regular',
            'price' => 25000,
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

    public function test_can_create_dine_in_order(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'dine_in',
                'table_id' => $this->table->id,
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_type', 'dine_in')
            ->assertJsonPath('data.subtotal', 44000)
            ->assertJsonPath('data.total', 44000)
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('orders', [
            'order_type' => 'dine_in',
            'table_id' => $this->table->id,
        ]);

        // table should now be marked occupied
        $this->assertDatabaseHas('dining_tables', [
            'id' => $this->table->id,
            'status' => 'occupied',
        ]);
    }

    public function test_can_create_takeaway_order_without_table(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_type', 'takeaway')
            ->assertJsonMissingPath('data.table_id');

        $this->assertDatabaseHas('orders', [
            'order_type' => 'takeaway',
            'table_id' => null,
        ]);
    }

    public function test_dine_in_order_requires_table_id(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'dine_in',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['table_id']);
    }

    public function test_order_requires_at_least_one_item(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_rejects_nonexistent_product_variant(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'items' => [
                    ['product_variant_id' => 999, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_variant_id']);
    }

    public function test_order_calculates_subtotal_correctly_with_multiple_items(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 2],  // 22000 * 2 = 44000
                    ['product_variant_id' => $this->variant2->id, 'quantity' => 1], // 25000 * 1 = 25000
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subtotal', 69000)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_order_applies_discount_correctly(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'discount' => 5000,
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1], // 22000
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subtotal', 22000)
            ->assertJsonPath('data.discount', 5000)
            ->assertJsonPath('data.total', 17000);
    }

    public function test_order_item_price_is_snapshotted_not_live(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ]);

        // Price changes AFTER the order was placed
        $this->variant->update(['price' => 99000]);

        $order = \App\Models\Order::first();

        // Order item should still reflect the original price, not the new one
        $this->assertEquals(22000, $order->items->first()->price);
    }

    public function test_can_list_orders(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_single_order(): void
    {
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'order_type' => 'takeaway',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ]);

        $orderId = $createResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/orders/{$orderId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $orderId);
    }

    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', [
            'order_type' => 'takeaway',
            'items' => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(401);
    }
}
