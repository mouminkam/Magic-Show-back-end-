<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $tax = $subtotal * 0.07;
        $total = $subtotal + $tax;
        $shippingAddress = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'phone' => fake()->phoneNumber(),
        ];

        return [
            'customer_id' => Customer::factory(),
            'order_number' => 'ORD-' . strtoupper(Str::random(10)),
            'status' => Order::STATUS_PENDING,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'payment_method' => 'cash',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $total,
            'currency' => 'SAR',
            'shipping_address' => $shippingAddress,
        ];
    }
}
