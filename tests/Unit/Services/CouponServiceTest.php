<?php

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Models\Customer;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $couponService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->couponService = app(CouponService::class);
    }

    // ─── validateCoupon ────────────────────────────────────────────────────────

    #[Test]
    public function validate_coupon_returns_invalid_for_non_existent_code(): void
    {
        $result = $this->couponService->validateCoupon('NO_SUCH_COUPON');

        $this->assertFalse($result['valid']);
        $this->assertNull($result['coupon']);
    }

    #[Test]
    public function validate_coupon_returns_invalid_for_inactive_coupon(): void
    {
        Coupon::factory()->create([
            'code'      => 'INACTIVE',
            'is_active' => false,
        ]);

        $result = $this->couponService->validateCoupon('INACTIVE');

        $this->assertFalse($result['valid']);
    }

    #[Test]
    public function validate_coupon_returns_invalid_for_expired_coupon(): void
    {
        Coupon::factory()->create([
            'code'       => 'EXPIRED',
            'is_active'  => true,
            'expires_at' => now()->subDay(),
        ]);

        $result = $this->couponService->validateCoupon('EXPIRED');

        $this->assertFalse($result['valid']);
    }

    #[Test]
    public function validate_coupon_returns_invalid_when_usage_limit_exhausted(): void
    {
        Coupon::factory()->create([
            'code'        => 'LIMIT1',
            'is_active'   => true,
            'usage_limit' => 1,
            'used_count'  => 1,
        ]);

        $result = $this->couponService->validateCoupon('LIMIT1', null, 100);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('استنفاد', $result['message']);
    }

    #[Test]
    public function validate_coupon_fails_when_order_amount_below_minimum(): void
    {
        Coupon::factory()->create([
            'code'           => 'MIN500',
            'is_active'      => true,
            'minimum_amount' => 500,
        ]);

        $result = $this->couponService->validateCoupon('MIN500', null, 200);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('الحد الأدنى', $result['message']);
    }

    #[Test]
    public function validate_coupon_passes_for_valid_coupon(): void
    {
        Coupon::factory()->create([
            'code'        => 'VALID10',
            'is_active'   => true,
            'usage_limit' => null,
        ]);

        $result = $this->couponService->validateCoupon('VALID10', null, 300);

        $this->assertTrue($result['valid']);
        $this->assertNotNull($result['coupon']);
    }

    #[Test]
    public function validate_coupon_passes_when_minimum_amount_met(): void
    {
        Coupon::factory()->create([
            'code'           => 'MIN100',
            'is_active'      => true,
            'minimum_amount' => 100,
            'usage_limit'    => null,
        ]);

        $result = $this->couponService->validateCoupon('MIN100', null, 150);

        $this->assertTrue($result['valid']);
    }

    // ─── applyCoupon ───────────────────────────────────────────────────────────

    #[Test]
    public function apply_coupon_calculates_percentage_discount(): void
    {
        $coupon = Coupon::factory()->create([
            'type'             => 'percentage',
            'value'            => 20,
            'minimum_amount'   => null,
            'maximum_discount' => null,
        ]);

        $result = $this->couponService->applyCoupon($coupon, 100);

        $this->assertEquals(20, $result['discount_amount']);
        $this->assertEquals(80, $result['order_total_after_discount']);
    }

    #[Test]
    public function apply_coupon_calculates_fixed_amount_discount(): void
    {
        $coupon = Coupon::factory()->create([
            'type'           => 'fixed_amount',
            'value'          => 30,
            'minimum_amount' => null,
        ]);

        $result = $this->couponService->applyCoupon($coupon, 100);

        $this->assertEquals(30, $result['discount_amount']);
        $this->assertEquals(70, $result['order_total_after_discount']);
    }

    #[Test]
    public function apply_coupon_returns_zero_discount_when_minimum_not_met(): void
    {
        $coupon = Coupon::factory()->create([
            'type'           => 'percentage',
            'value'          => 20,
            'minimum_amount' => 500,
        ]);

        $result = $this->couponService->applyCoupon($coupon, 200);

        $this->assertEquals(0, $result['discount_amount']);
    }

    #[Test]
    public function apply_coupon_does_not_produce_negative_total(): void
    {
        $coupon = Coupon::factory()->create([
            'type'           => 'fixed_amount',
            'value'          => 9999,
            'minimum_amount' => null,
        ]);

        $result = $this->couponService->applyCoupon($coupon, 50);

        $this->assertGreaterThanOrEqual(0, $result['order_total_after_discount']);
    }
}
