<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CouponService
{
    /**
     * Get coupons with filters and pagination
     */
    public function getCoupons(Request $request)
    {
        $query = Coupon::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true)
                          ->valid()
                          ->available();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'not_started':
                    $query->notStarted();
                    break;
                case 'exhausted':
                    $query->whereRaw('used_count >= usage_limit AND usage_limit IS NOT NULL');
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        // Is active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1' || $request->is_active === true);
        }

        // Is public filter
        if ($request->filled('is_public')) {
            $query->where('is_public', $request->is_public === '1' || $request->is_public === true);
        }

        // Sort functionality
        $sortBy = (string) $request->get('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc'));
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc';
        $allowedSorts = ['created_at', 'expires_at', 'used_count', 'code', 'name'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(20);
    }

    /**
     * Create a new coupon
     */
    public function createCoupon(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {
            try {
                // Handle JSON fields
                $jsonFields = [
                    'applicable_products',
                    'applicable_categories',
                    'excluded_products',
                    'excluded_categories',
                    'customer_groups',
                ];

                foreach ($jsonFields as $field) {
                    if (isset($data[$field]) && is_array($data[$field])) {
                        $data[$field] = array_filter($data[$field]); // Remove empty values
                        $data[$field] = !empty($data[$field]) ? $data[$field] : null;
                    } else {
                        $data[$field] = null;
                    }
                }

                // Handle boolean fields
                $data['is_active'] = isset($data['is_active']) ? (bool)$data['is_active'] : true;
                $data['is_public'] = isset($data['is_public']) ? (bool)$data['is_public'] : true;

                // Handle usage_limit_per_customer default
                if (!isset($data['usage_limit_per_customer'])) {
                    $data['usage_limit_per_customer'] = 1;
                }

                // Auto-generate code if not provided
                if (empty($data['code'])) {
                    $data['code'] = strtoupper(\Illuminate\Support\Str::random(8));
                }

                $coupon = Coupon::create($data);

                Log::info('تم إنشاء كوبون جديد', [
                    'coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->code,
                    'user_id' => auth()->id(),
                ]);

                return $coupon;

            } catch (\Exception $e) {
                Log::error('خطأ في إنشاء الكوبون', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update a coupon
     */
    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            try {
                // Handle JSON fields
                $jsonFields = [
                    'applicable_products',
                    'applicable_categories',
                    'excluded_products',
                    'excluded_categories',
                    'customer_groups',
                ];

                foreach ($jsonFields as $field) {
                    if (isset($data[$field]) && is_array($data[$field])) {
                        $data[$field] = array_filter($data[$field]); // Remove empty values
                        $data[$field] = !empty($data[$field]) ? $data[$field] : null;
                    } elseif (!isset($data[$field])) {
                        // Don't update if not provided
                        unset($data[$field]);
                    }
                }

                // Handle boolean fields
                if (isset($data['is_active'])) {
                    $data['is_active'] = (bool)$data['is_active'];
                }
                if (isset($data['is_public'])) {
                    $data['is_public'] = (bool)$data['is_public'];
                }

                $coupon->update($data);

                Log::info('تم تحديث الكوبون', [
                    'coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->code,
                    'user_id' => auth()->id(),
                ]);

                return $coupon->fresh();

            } catch (\Exception $e) {
                Log::error('خطأ في تحديث الكوبون', [
                    'error' => $e->getMessage(),
                    'coupon_id' => $coupon->id,
                    'data' => $data,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete a coupon
     */
    public function deleteCoupon(Coupon $coupon): bool
    {
        return DB::transaction(function () use ($coupon) {
            try {
                $couponCode = $coupon->code;
                $couponId = $coupon->id;

                // Delete related coupon usages
                CouponUsage::where('coupon_id', $couponId)->delete();

                // Delete the coupon
                $coupon->delete();

                Log::info('تم حذف الكوبون', [
                    'coupon_id' => $couponId,
                    'coupon_code' => $couponCode,
                    'user_id' => auth()->id(),
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('خطأ في حذف الكوبون', [
                    'error' => $e->getMessage(),
                    'coupon_id' => $coupon->id,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get coupon statistics
     */
    public function getCouponStats(): array
    {
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::where('is_active', true)
            ->valid()
            ->available()
            ->count();
        $expiredCoupons = Coupon::expired()->count();
        $totalUsageCount = CouponUsage::count();
        $totalDiscountAmount = CouponUsage::sum('discount_amount');

        $mostUsedCoupons = Coupon::withCount('usages')
            ->orderBy('usages_count', 'desc')
            ->limit(5)
            ->get();

        $recentCoupons = Coupon::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_coupons' => $totalCoupons,
            'active_coupons' => $activeCoupons,
            'expired_coupons' => $expiredCoupons,
            'total_usage_count' => $totalUsageCount,
            'total_discount_amount' => round($totalDiscountAmount, 2),
            'most_used_coupons' => $mostUsedCoupons,
            'recent_coupons' => $recentCoupons,
        ];
    }

    /**
     * Validate coupon
     */
    public function validateCoupon(string $code, ?Customer $customer = null, ?float $orderAmount = null): array
    {
        try {
            $coupon = Coupon::where('code', $code)->first();

            if (!$coupon) {
                return [
                    'valid' => false,
                    'coupon' => null,
                    'message' => 'الكوبون غير موجود',
                ];
            }

            if (!$coupon->is_active) {
                return [
                    'valid' => false,
                    'coupon' => $coupon,
                    'message' => 'الكوبون غير نشط',
                ];
            }

            if (!$coupon->isValid()) {
                if ($coupon->isExpired()) {
                    return [
                        'valid' => false,
                        'coupon' => $coupon,
                        'message' => 'الكوبون منتهي الصلاحية',
                    ];
                }
                if (!$coupon->hasStarted()) {
                    return [
                        'valid' => false,
                        'coupon' => $coupon,
                        'message' => 'الكوبون لم يبدأ بعد',
                    ];
                }
            }

            if (!$coupon->isAvailable()) {
                return [
                    'valid' => false,
                    'coupon' => $coupon,
                    'message' => 'تم استنفاد حد استخدام الكوبون',
                ];
            }

            if ($customer && !$coupon->canBeUsedBy($customer)) {
                // Check if it's a customer group issue
                if ($coupon->customer_groups && count($coupon->customer_groups) > 0) {
                    $customerGroup = $customer->customer_group ?? $customer->group ?? null;
                    if (!$customerGroup || !in_array($customerGroup, $coupon->customer_groups)) {
                        return [
                            'valid' => false,
                            'coupon' => $coupon,
                            'message' => 'هذا الكوبون متاح فقط لمجموعات عملاء محددة',
                        ];
                    }
                }
                
                return [
                    'valid' => false,
                    'coupon' => $coupon,
                    'message' => 'لقد استخدمت هذا الكوبون الحد الأقصى المسموح به',
                ];
            }
            
            // Check customer groups even if customer is not provided (for validation without customer)
            if ($coupon->customer_groups && count($coupon->customer_groups) > 0 && !$customer) {
                // This is a restricted coupon, but we can't validate without customer
                // Just return valid for now, actual validation will happen when customer is provided
            }

            if ($orderAmount !== null && !$coupon->canBeAppliedToAmount($orderAmount)) {
                return [
                    'valid' => false,
                    'coupon' => $coupon,
                    'message' => 'قيمة الطلب أقل من الحد الأدنى المطلوب',
                ];
            }

            return [
                'valid' => true,
                'coupon' => $coupon,
                'message' => 'الكوبون صالح للاستخدام',
            ];

        } catch (\Exception $e) {
            Log::error('خطأ في التحقق من الكوبون', [
                'error' => $e->getMessage(),
                'code' => $code,
            ]);

            return [
                'valid' => false,
                'coupon' => null,
                'message' => 'حدث خطأ أثناء التحقق من الكوبون',
            ];
        }
    }

    /**
     * Apply coupon and calculate discount
     */
    public function applyCoupon(Coupon $coupon, float $orderAmount, array $orderItems = []): array
    {
        try {
            // Check if coupon can be applied to order amount
            if (!$coupon->canBeAppliedToAmount($orderAmount)) {
                return [
                    'discount_amount' => 0,
                    'order_total_after_discount' => $orderAmount,
                    'message' => 'قيمة الطلب أقل من الحد الأدنى المطلوب',
                ];
            }

            // Check if coupon applies to products/categories
            if (!empty($orderItems)) {
                $applicable = false;
                foreach ($orderItems as $item) {
                    $productId = $item['product_id'] ?? null;
                    $categoryId = $item['category_id'] ?? null;

                    if ($productId) {
                        // Check if product is excluded
                        if ($coupon->excludesProduct($productId)) {
                            continue;
                        }

                        // Check if product is applicable
                        if ($coupon->appliesToProduct($productId)) {
                            $applicable = true;
                            break;
                        }
                    }

                    if ($categoryId) {
                        // Check if category is excluded
                        if ($coupon->excludesCategory($categoryId)) {
                            continue;
                        }

                        // Check if category is applicable
                        if ($coupon->appliesToCategory($categoryId)) {
                            $applicable = true;
                            break;
                        }
                    }
                }

                // If coupon has specific products/categories and none match, return 0 discount
                if (($coupon->applicable_products || $coupon->applicable_categories) && !$applicable) {
                    return [
                        'discount_amount' => 0,
                        'order_total_after_discount' => $orderAmount,
                        'message' => 'الكوبون لا ينطبق على المنتجات المحددة',
                    ];
                }
            }

            // Calculate discount using model method
            $discountAmount = $coupon->calculateDiscount($orderAmount);
            $orderTotalAfterDiscount = $orderAmount - $discountAmount;

            return [
                'discount_amount' => $discountAmount,
                'order_total_after_discount' => max(0, $orderTotalAfterDiscount),
                'message' => 'تم تطبيق الكوبون بنجاح',
            ];

        } catch (\Exception $e) {
            Log::error('خطأ في تطبيق الكوبون', [
                'error' => $e->getMessage(),
                'coupon_id' => $coupon->id,
                'order_amount' => $orderAmount,
            ]);

            return [
                'discount_amount' => 0,
                'order_total_after_discount' => $orderAmount,
                'message' => 'حدث خطأ أثناء تطبيق الكوبون',
            ];
        }
    }

    /**
     * Record coupon usage
     */
    public function recordCouponUsage(Coupon $coupon, Order $order, Customer $customer, float $discountAmount): CouponUsage
    {
        return DB::transaction(function () use ($coupon, $order, $customer, $discountAmount) {
            try {
                $orderTotal = $order->subtotal ?? $order->total_amount;
                $orderTotalAfterDiscount = $orderTotal - $discountAmount;

                $couponUsage = CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'customer_id' => $customer->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                    'order_total' => $orderTotal,
                    'order_total_after_discount' => $orderTotalAfterDiscount,
                    'coupon_code' => $coupon->code,
                    'used_at' => now(),
                ]);

                // Increment usage count
                $coupon->incrementUsage();

                Log::info('تم تسجيل استخدام الكوبون', [
                    'coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->code,
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'discount_amount' => $discountAmount,
                    'user_id' => auth()->id(),
                ]);

                return $couponUsage;

            } catch (\Exception $e) {
                Log::error('خطأ في تسجيل استخدام الكوبون', [
                    'error' => $e->getMessage(),
                    'coupon_id' => $coupon->id,
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get coupon usages
     */
    public function getCouponUsages(Coupon $coupon, Request $request)
    {
        $query = CouponUsage::where('coupon_id', $coupon->id)
            ->with(['customer', 'order']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('coupon_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('order_number', 'like', "%{$search}%");
                  });
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('used_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('used_at', '<=', $request->date_to);
        }

        // Sort functionality. Both the column and the direction were taken raw
        // from the query string and handed to orderBy(); whitelist each.
        $allowedSorts = ['used_at', 'created_at', 'discount_amount', 'order_total'];
        $sortBy = (string) $request->get('sort_by', 'used_at');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc'));

        $query->orderBy(
            in_array($sortBy, $allowedSorts, true) ? $sortBy : 'used_at',
            in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc'
        );

        return $query->paginate(20);
    }
}
