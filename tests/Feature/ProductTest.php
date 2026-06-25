<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Override;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected Category $category;

    #[Override]
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

        $this->category = Category::create(['name' => 'Coffee']);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Cappuccino',
            'is_active' => true
        ]);

        $product->variants()->create(['name' => 'Hot - Regular', 'price' => 22000]);

        $reponse = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products');

        $reponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'category', 'variants']
                ]
            ]);
    }

    public function test_can_create_products_with_variants(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Cafe Latte',
            'description' => 'Espresso with steamed milk',
            'is_active' => true,
            'variants' => [
                ['name' => 'Hot - Regular', 'price' => 23000],
                ['name' => 'Iced - Regular', 'price' => 26000],
            ]
        ];

        $reponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/products', $payload);

        $reponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Cafe Latte')
            ->assertJsonCount(2, 'data.variants');

        $this->assertDatabaseHas('products', ['name' => 'Cafe Latte']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Hot - Regular', 'price' => 23000]);
        $this->assertDatabaseHas('product_variants', ['name' => 'Iced - Regular', 'price' => 26000]);
    }

    public function test_cannot_create_product_without_variants(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/products', [
                'category_id' => $this->category->id,
                'name' => 'No Variant Product'
            ]);

        $response->assertStatus(422);
    }

    public function test_can_show_single_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Matcha Latte',
            'is_active' => true,
        ]);
        $product->variants()->create(['name' => 'Iced - Regular', 'price' => 27000]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Matcha Latte')
            ->assertJsonCount(1, 'data.variants');
    }

    public function test_can_update_product_and_replace_variants(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Cappuccino',
            'is_active' => true,
        ]);
        $product->variants()->create(['name' => 'Hot - Regular', 'price' => 22000]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/products/{$product->id}", [
                'category_id' => $this->category->id,
                'name' => 'Cappuccino Deluxe',
                'is_active' => true,
                'variants' => [
                    ['name' => 'Hot - Large', 'price' => 25000],
                ],
            ]);

        // dd($response->json(), $product->fresh()->toArray());

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Cappuccino Deluxe')
            ->assertJsonCount(1, 'data.variants');

        // old variant should be gone, replaced by new one
        $this->assertDatabaseMissing('product_variants', ['name' => 'Hot - Regular']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Hot - Large']);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Croissant',
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }
}
