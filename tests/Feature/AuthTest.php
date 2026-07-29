<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_customer_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customer' => ['id', 'first_name', 'last_name', 'email'],
                    'access_token',
                    'token_type',
                ],
            ])
            ->assertJson(['data' => ['token_type' => 'Bearer']]);

        $this->assertDatabaseHas('customers', ['email' => 'test@example.com']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        Customer::create([
            'first_name' => 'Login',
            'last_name' => 'User',
            'email' => 'login@example.com',
            'phone' => '+1234567890',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customer',
                    'access_token',
                    'token_type',
                ],
            ]);
    }

    public function test_login_fails_for_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }
}
