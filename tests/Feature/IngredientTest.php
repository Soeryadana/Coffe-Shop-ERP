<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IngredientTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_ingredients(): void
    {
        Ingredient::create([
            'name'  => 'Coffee Beans',
            'unit'  => 'g',
            'stock_quantity' => 5000,
            'min_stock_alert' => 500,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/ingredients', [
                'name'  => 'Fresh Milk',
                'unit' => 'ml',
                'stock_quantity' => 10000,
                'min_stock_alert' => 1000
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Fresh Milk']);

        $this->assertDatabaseHas('ingredients', ['name' => 'Fresh Milk']);
    }

    public function test_cannot_create_ingredient_with_invalid_unit(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/ingredients', [
                'name' => 'Invalid Unit Item',
                'unit' => 'liters',
                'stock_quantity' => 100,
                'min_stock_alert' => 10,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_show_single_ingredient_with_stock_movements(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Matcha Powder',
            'unit' => 'g',
            'stock_quantity' => 1000,
            'min_stock_alert' => 100,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/ingredients/{$ingredient->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Matcha Powder'])
            ->assertJsonStructure(['id', 'name', 'stock_movements']);
    }

    public function test_can_update_ingredient(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Sugar Syrup',
            'unit' => 'ml',
            'stock_quantity' => 3000,
            'min_stock_alert' => 300,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/ingredients/{$ingredient->id}", [
                'name' => 'Vanilla Syrup',
                'unit' => 'ml',
                'stock_quantity' => 3000,
                'min_stock_alert' => 300,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Vanilla Syrup']);
    }

    public function test_can_delete_ingredient(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Ice Cubes',
            'unit' => 'g',
            'stock_quantity' => 20000,
            'min_stock_alert' => 2000,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/ingredients/{$ingredient->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('ingredients', ['id' => $ingredient->id]);
    }

    public function test_can_adjust_stock_in(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Coffee Beans',
            'unit' => 'g',
            'stock_quantity' => 1000,
            'min_stock_alert' => 100,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/ingredients/{$ingredient->id}/stock-adjustment", [
                'type' => 'in',
                'quantity' => 500,
                'reason' => 'Restock from supplier',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('stock_quantity', 1500);

        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'in',
            'quantity' => 500,
        ]);
    }

    public function test_can_adjust_stock_out(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Coffee Beans',
            'unit' => 'g',
            'stock_quantity' => 1000,
            'min_stock_alert' => 100,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/ingredients/{$ingredient->id}/stock-adjustment", [
                'type' => 'out',
                'quantity' => 200,
                'reason' => 'Waste / spillage',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('stock_quantity', 800);
    }

    public function test_stock_adjustment_requires_valid_type(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Coffee Beans',
            'unit' => 'g',
            'stock_quantity' => 1000,
            'min_stock_alert' => 100,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/ingredients/{$ingredient->id}/stock-adjustment", [
                'type' => 'invalid_type',
                'quantity' => 200,
            ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_access_ingredients(): void
    {
        $response = $this->getJson('/api/ingredients');

        $response->assertStatus(401);
    }
}
