<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DiningTableTest extends TestCase
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

    public function test_can_list_dining_tables(): void
    {
        DiningTable::create(['table_number' => 'T01', 'status' => 'available']);
        DiningTable::create(['table_number' => 'T02', 'status' => 'occupied']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/tables');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_can_create_dining_table(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/tables', [
                'table_number' => 'T03',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['table_number' => 'T03', 'status' => 'available']);

        $this->assertDatabaseHas('dining_tables', ['table_number' => 'T03']);
    }

    public function test_cannot_create_dining_table_without_number(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/tables', [
                'table_number' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_update_dining_table_status(): void
    {
        $table = DiningTable::create(['table_number' => 'T01', 'status' => 'available']);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/tables/{$table->id}/status", [
                'status' => 'occupied',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'occupied']);

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'status' => 'occupied',
        ]);
    }

    public function test_cannot_update_dining_table_with_invalid_status(): void
    {
        $table = DiningTable::create(['table_number' => 'T01', 'status' => 'available']);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/tables/{$table->id}/status", [
                'status' => 'closed', // not in available/occupied/reserved
            ]);

        $response->assertStatus(422);
    }

    public function test_update_status_returns_404_for_nonexistent_table(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->patchJson('/api/tables/999/status', [
                'status' => 'occupied',
            ]);

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_access_dining_tables(): void
    {
        $response = $this->getJson('/api/tables');

        $response->assertStatus(401);
    }
}
