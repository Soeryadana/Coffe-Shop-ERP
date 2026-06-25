<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Override;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

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
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_categories(): void
    {
        Category::create(['name' => 'Coffee']);
        Category::create(['name' => 'Snacks']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_can_create_category(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/categories', [
                'name' => 'Pastry'
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Pastry']);

        $this->assertDatabaseHas('categories', ['name' => 'Pastry']);
    }

    public function test_cannot_create_category_without_name(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/categories', [
                'name' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_show_single_category_with_producst(): void
    {
        $category = Category::create(['name' => 'Coffee']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Coffee'])
            ->assertJsonStructure(['id', 'name', 'products']);
    }

    public function test_show_returns_404_for_nonexistent_category(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/categories/999');

        $response->assertStatus(404);
    }

    public function test_can_update_category(): void
    {
        $category = Category::create(['name' => 'Coffee']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Premium Coffee'
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Premium Coffee']);

        $this->assertDatabaseHas('categories', ['name' => 'Premium Coffee']);
    }

    public function test_can_delete_category(): void
    {
        $category = Category::create(['name' => 'Coffee']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401);
    }
}
