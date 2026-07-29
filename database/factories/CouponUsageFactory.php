<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CouponUsage>
 */
class CouponUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     * Requires coupon_id, customer_id, order_id to be passed when creating.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $coupon = Coupon::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderTotal = 100;
        $discountAmount = 10;
        $totalAfterDiscount = $orderTotal - $discountAmount;

        return [
            'coupon_id' => $coupon->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
            'order_total' => $orderTotal,
            'order_total_after_discount' => $totalAfterDiscount,
            'coupon_code' => $coupon->code,
            'used_at' => now(),
        ];
    }

    public function forCoupon(Coupon $coupon): static
    {
        return $this->state(fn (array $attributes) => [
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }
}
