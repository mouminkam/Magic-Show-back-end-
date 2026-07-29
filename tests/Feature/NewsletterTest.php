<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_creates_subscriber(): void
    {
        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'subscribe@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['subscribed' => true],
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'subscribe@example.com',
        ]);
    }

    public function test_subscribe_idempotent_for_existing_subscriber(): void
    {
        NewsletterSubscriber::create([
            'email' => 'existing@example.com',
            'subscribed_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['subscribed' => true]]);
    }

    public function test_subscribe_validates_email(): void
    {
        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
    }
}
