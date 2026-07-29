<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        return [
            'name' => $name,
            'sku' => 'SKU-' . strtoupper(Str::random(8)),
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'price' => fake()->randomFloat(2, 10, 500),
            'quantity' => fake()->numberBetween(0, 100),
            'track_quantity' => true,
            'allow_backorder' => false,
            'is_active' => true,
        ];
    }

    public function withoutTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_quantity' => false,
        ]);
    }

    public function withBackorder(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_backorder' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
