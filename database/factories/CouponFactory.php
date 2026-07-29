<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'name' => fake()->words(3, true),
            'type' => 'percentage',
            'value' => fake()->numberBetween(5, 50),
            'is_active' => true,
            'usage_limit' => null,
            'usage_limit_per_customer' => 1,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
        ];
    }

    public function percentage(int $value = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'value' => $value,
        ]);
    }

    public function fixedAmount(float $value = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed_amount',
            'value' => $value,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 0,
            'used_count' => 1,
        ]);
    }

    public function usageLimitReached(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 1,
            'used_count' => 1,
        ]);
    }
}
