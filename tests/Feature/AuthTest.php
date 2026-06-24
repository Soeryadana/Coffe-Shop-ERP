<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
                'token',
            ]);
    }

    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ]);
    }

    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'doesnotexsist@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ]);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@test.com',
            'password' => Hash::make('password123'),
            'role' => 'cashier'
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'email' => 'cashier@test.com',
                'role' => 'cashier'
            ]);
    }

    public function test_unauthorized_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
            'role' => 'cashier'
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer ${token}")
            ->postJson('/api/logout');

        $response->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
