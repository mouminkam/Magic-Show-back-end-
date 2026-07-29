<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Services\CouponService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderService
{
    public function __construct(
        protected CouponService $couponService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Get orders with filters and pagination
     */
    public function getOrders(Request $request)
    {
        $query = Order::with(['customer', 'orderItems.product']);

        // Search by order number or customer name/email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%")
                                   ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter (3 حالات فقط: pending, delivered=مكتمل, cancelled - مزامنة مع الفرونت)
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'delivered') {
                $query->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED]);
            } else {
                $query->where('status', $status);
            }
        }

        // Payment status filter
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Contact status filter
        if ($request->filled('contact_status')) {
            $query->where('contact_status', $request->contact_status);
        }

        // Customer filter
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Country filter (via customer)
        if ($request->filled('country')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('country', $request->country);
            });
        }

        // Sort functionality
        $sortBy = (string) $request->get('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc'));
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc';
        $allowedSorts = ['order_number', 'created_at', 'total_amount', 'status'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(20);
    }

    /**
     * Place order from validated store request (cart_items + shipping).
     * Customer is identified via Auth::id(); names/phone come from customers table.
     * Totals are calculated on the backend; no prices from frontend.
     *
     * @param array $data Validated data: cart_items [{ product_id, quantity }], shipping { address_line1, city, country }
     * @return Order
     * @throws \RuntimeException
     */
    public function placeOrder(array $data): Order
    {
        $customerId = Auth::id();
        if (!$customerId) {
            throw new \RuntimeException(__('errors.unauthorized'));
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new \RuntimeException(__('errors.customer_not_found'));
        }

        $shippingAddress = [
            'street' => $data['shipping']['address_line1'],
            'city' => $data['shipping']['city'],
            'country' => $data['shipping']['country'],
        ];

        return DB::transaction(function () use ($data, $customerId, $shippingAddress) {
            $cartItems = $data['cart_items'];
            $productIds = array_column($cartItems, 'product_id');
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $orderItemsData = [];

            // Stock validation before any writes
            foreach ($cartItems as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $product = $products->get($productId);

                if (!$product) {
                    throw new \RuntimeException(__('errors.product_not_found'));
                }

                if ($product->track_quantity && !$product->allow_backorder) {
                    $available = $product->inventory()->exists()
                        ? ($product->total_inventory_quantity ?? 0)
                        : ($product->quantity ?? 0);
                    if ($available < $quantity) {
                        throw new \RuntimeException(
                            __('errors.insufficient_stock', ['name' => $product->name, 'available' => $available])
                                ?? "Insufficient stock for {$product->name}"
                        );
                    }
                }
            }

            foreach ($cartItems as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $product = $products->get($productId);

                $unitPrice = (float) ($product->sale_price ?? $product->price ?? 0);
                if ($unitPrice < 0) {
                    $unitPrice = 0;
                }
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                $productAttributes = [];
                if (!empty($item['size'])) {
                    $productAttributes['size'] = $item['size'];
                }
                if (!empty($item['color'])) {
                    $productAttributes['color'] = $item['color'];
                }

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'product_attributes' => $productAttributes,
                ];
            }

            // Deduct inventory. POST /api/v1/orders previously validated stock
            // but never decremented it, so unlimited orders could be placed
            // against a single unit and the cart/checkout flow (createFromCart)
            // and this flow disagreed about available stock.
            foreach ($orderItemsData as $item) {
                $this->inventoryService->deductForOrder(
                    $products->get($item['product_id']),
                    $item['quantity']
                );
            }

            // Tax is disabled for Magic Shoe – always zero.
            $taxAmount = 0;
            $shippingAmount = 0;
            $discountAmount = 0;
            $couponCode = null;
            $coupon = null;

            if (!empty($data['coupon_code'])) {
                // Lock the coupon row to prevent race conditions (double-spend)
                $lockedCoupon = \App\Models\Coupon::where('code', trim($data['coupon_code']))
                    ->lockForUpdate()
                    ->first();
                if (!$lockedCoupon) {
                    throw new \RuntimeException(__('messages.invalid_coupon'));
                }
                $customer = Customer::find($customerId);
                $validation = $this->couponService->validateCoupon(
                    trim($data['coupon_code']),
                    $customer,
                    $subtotal
                );
                if (!$validation['valid'] || !$validation['coupon']) {
                    throw new \RuntimeException($validation['message'] ?? __('messages.invalid_coupon'));
                }
                $coupon = $lockedCoupon;
                $applyResult = $this->couponService->applyCoupon($coupon, $subtotal, $orderItemsData);
                $discountAmount = (float) ($applyResult['discount_amount'] ?? 0);
                $couponCode = $coupon->code;
            }

            $totalAmount = max(0, round($subtotal + $shippingAmount - $discountAmount, 2));

            $order = Order::create([
                'customer_id' => $customerId,
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                // Single project currency: Syrian Pound (SYP)
                'currency' => 'SYP',
                'shipping_address' => $shippingAddress,
                'billing_address' => $shippingAddress,
                'contact_status' => 'pending',
                'coupon_code' => $couponCode,
            ]);

            foreach ($orderItemsData as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'product_attributes' => $item['product_attributes'] ?? [],
                ]);
            }

            if ($coupon && $discountAmount > 0) {
                $customer = Customer::find($customerId);
                $this->couponService->recordCouponUsage($coupon, $order, $customer, $discountAmount);
            }

            return $order->load(['customer', 'orderItems.product']);
        });
    }

    /**
     * List orders for the authenticated customer (order history).
     *
     * @param int $customerId
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listOrdersForCustomer(int $customerId, int $page = 1)
    {
        return Order::forCustomer($customerId)
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $page);
    }

    /**
     * List orders for admin (with customer details for WhatsApp etc.).
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listOrdersForAdmin()
    {
        return Order::with(['customer', 'orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Update order status (admin). Allowed: pending -> paid (mark successful), pending -> cancelled.
     *
     * @param Order $order
     * @param string $status One of: paid, cancelled
     * @return Order
     * @throws \InvalidArgumentException
     */
    public function updateStatus(Order $order, string $status): Order
    {
        if (!in_array($status, ['paid', 'cancelled'], true)) {
            throw new \InvalidArgumentException(__('orders.invalid_status', ['status' => $status]));
        }

        return DB::transaction(function () use ($order, $status) {
            // Re-read under a row lock so two admins clicking at the same time
            // cannot both pass the guards below and, e.g., restock twice.
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $currentStatus = $order->status;
            $currentPaymentStatus = $order->payment_status;

            if ($status === 'paid') {
                if ($currentStatus === Order::STATUS_CANCELLED) {
                    throw new \InvalidArgumentException(__('orders.cannot_mark_cancelled_as_paid'));
                }
                if ($currentPaymentStatus === Order::PAYMENT_STATUS_PAID) {
                    // Already paid: idempotent no-op rather than rewriting confirmed_at.
                    return $order->fresh(['customer', 'orderItems.product']);
                }

                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'status' => Order::STATUS_CONFIRMED,
                    'confirmed_at' => $order->confirmed_at ?? now(),
                ]);
            } else {
                if ($currentPaymentStatus === Order::PAYMENT_STATUS_PAID) {
                    throw new \InvalidArgumentException(__('orders.cannot_cancel_paid_order'));
                }
                if ($currentStatus === Order::STATUS_CANCELLED) {
                    // Already cancelled: do not restock a second time.
                    return $order->fresh(['customer', 'orderItems.product']);
                }

                $order->update([
                    'status' => Order::STATUS_CANCELLED,
                    'cancelled_at' => $order->cancelled_at ?? now(),
                ]);

                $this->restoreStockForOrder($order);
            }

            return $order->fresh(['customer', 'orderItems.product']);
        });
    }

    /**
     * Put the stock reserved by an order back into inventory.
     */
    protected function restoreStockForOrder(Order $order): void
    {
        $order->loadMissing('orderItems.product');

        foreach ($order->orderItems as $item) {
            if ($item->product) {
                $this->inventoryService->restoreForOrder($item->product, (int) $item->quantity);
            }
        }
    }

    /**
     * Create order from cart checkout (API cart/checkout flow).
     * Validates stock, deducts inventory, applies coupon, creates order.
     *
     * @param array $data Validated checkout data: items, shipping_address, payment_method, coupon_code?
     * @param int $customerId
     * @return Order
     */
    public function createFromCart(array $data, int $customerId): Order
    {
        return DB::transaction(function () use ($data, $customerId) {
            $items = $data['items'];

            // Bulk-fetch all products from DB — never trust client-sent prices
            $productIds = array_column($items, 'id');
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $discount = 0;
            $coupon = null;

            // Validate products and stock (single loop, locked products)
            foreach ($items as $item) {
                $product = $products->get((int) $item['id']);
                if (!$product) {
                    throw new \RuntimeException(__('errors.product_not_found') . ' (ID: ' . $item['id'] . ')');
                }
                $available = $product->inventory()->exists()
                    ? $product->total_inventory_quantity
                    : ($product->quantity ?? 0);
                if ($product->track_quantity && !$product->allow_backorder && $available < (int) $item['quantity']) {
                    throw new \RuntimeException(
                        __('errors.insufficient_stock', ['name' => $product->name, 'available' => $available])
                    );
                }
            }

            // Deduct inventory
            foreach ($items as $item) {
                $product = $products->get((int) $item['id']);
                if ($product && $product->track_quantity && !$product->allow_backorder) {
                    $this->inventoryService->deductForOrder($product, (int) $item['quantity']);
                }
            }

            // Apply coupon (with row lock to prevent race conditions)
            if (!empty($data['coupon_code'])) {
                $coupon = \App\Models\Coupon::where('code', $data['coupon_code'])
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    })
                    ->lockForUpdate()
                    ->first();

                if ($coupon) {
                    if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                        throw new \RuntimeException(__('messages.coupon_expired'));
                    }
                    if ($coupon->usage_limit_per_customer) {
                        $customerUsageCount = $coupon->usages()->where('customer_id', $customerId)->count();
                        if ($customerUsageCount >= $coupon->usage_limit_per_customer) {
                            throw new \RuntimeException(__('messages.coupon_already_used'));
                        }
                    }
                } else {
                    throw new \RuntimeException(__('messages.invalid_coupon'));
                }
            }

            // Build order items using DB prices (never client-sent price)
            $orderItems = [];
            foreach ($items as $item) {
                $product = $products->get((int) $item['id']);
                $unitPrice = (float) ($product->sale_price ?? $product->price ?? 0);
                if ($unitPrice < 0) $unitPrice = 0;
                $qty = (int) $item['quantity'];
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;
                $orderItems[] = [
                    'product' => $product,
                    'id' => $product->id,
                    'qty' => $qty,
                    'unitPrice' => $unitPrice,
                    'lineTotal' => $lineTotal,
                ];
            }

            // Tax removed – always zero
            $tax = 0;

            if ($coupon) {
                // Minimum-order threshold was not enforced on this path, so a
                // "spend 100, get 20 off" coupon applied to a 5-unit order.
                if (!$coupon->canBeAppliedToAmount($subtotal)) {
                    throw new \RuntimeException(__('messages.coupon_minimum_not_met'));
                }

                if ($coupon->type === 'percentage') {
                    $discount = round($subtotal * ((float) $coupon->value / 100), 2);
                    if ($coupon->maximum_discount && $discount > $coupon->maximum_discount) {
                        $discount = (float) $coupon->maximum_discount;
                    }
                } elseif ($coupon->type === 'fixed_amount') {
                    $discount = min((float) $coupon->value, $subtotal);
                }

                $discount = max(0, round($discount, 2));

                // Only burn a redemption when the coupon actually did something.
                // Previously used_count was incremented unconditionally while the
                // CouponUsage row was only written when $discount > 0, so the
                // global and per-customer counters drifted apart.
                if ($discount > 0) {
                    $coupon->increment('used_count');
                }
            }

            $total = max(0, round($subtotal - $discount, 2));
            $paymentMethod = $this->mapPaymentMethod($data['payment_method'] ?? 'cash');

            $order = Order::create([
                'customer_id' => $customerId,
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_amount' => 0,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'currency' => 'SYP',
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'shipping_address' => $data['shipping_address'],
                'coupon_code' => $data['coupon_code'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['product']?->name ?? 'Product #' . $item['id'],
                    'product_sku' => $item['product']?->sku,
                    'quantity' => $item['qty'],
                    'unit_price' => $item['unitPrice'],
                    'total_price' => $item['lineTotal'],
                ]);
                $item['product']?->increment('sales_count', $item['qty']);
            }

            if ($coupon && $discount > 0) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'customer_id' => $customerId,
                    'order_id' => $order->id,
                    'discount_amount' => $discount,
                    'order_total' => $subtotal + $tax,
                    'order_total_after_discount' => $total,
                    'coupon_code' => $coupon->code,
                ]);
            }

            return $order;
        });
    }

    private function mapPaymentMethod(string $method): ?string
    {
        return match (strtolower($method)) {
            'cash' => 'cash',
            'card', 'stripe' => 'credit_card',
            'paypal' => 'paypal',
            'bank_transfer' => 'bank_transfer',
            default => 'cash',
        };
    }

    /**
     * Create a new order
     */
    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            try {
                // Validate and deduct inventory before creating order
                foreach ($data['order_items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $qty = (int) ($item['quantity'] ?? 1);
                    if ($product->track_quantity && !$product->allow_backorder) {
                        $available = $product->inventory()->exists()
                            ? $product->total_inventory_quantity
                            : ($product->quantity ?? 0);
                        if ($available < $qty) {
                            throw new \RuntimeException(
                                __('errors.insufficient_stock', ['name' => $product->name, 'available' => $available])
                            );
                        }
                        $this->inventoryService->deductForOrder($product, $qty);
                    }
                }

                // Calculate totals
                $totals = $this->calculateOrderTotals($data['order_items'], $data['coupon_code'] ?? null);

                // Create order
                $order = Order::create([
                    'customer_id' => $data['customer_id'],
                    'status' => $data['status'] ?? Order::STATUS_PENDING,
                    'payment_status' => $data['payment_status'] ?? Order::PAYMENT_STATUS_PENDING,
                    'payment_method' => $data['payment_method'] ?? null, // manual or null for now
                    'subtotal' => $totals['subtotal'],
                    // Tax disabled – always zero in calculated totals
                    'tax_amount' => $totals['tax_amount'],
                    'shipping_amount' => $totals['shipping_amount'],
                    'discount_amount' => $totals['discount_amount'],
                    'total_amount' => $totals['total'],
                    'currency' => 'SYP',
                    'shipping_address' => $data['shipping_address'],
                    'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
                    'shipping_method' => $data['shipping_method'] ?? null, // to_be_determined for now
                    'notes' => $data['notes'] ?? null,
                    'customer_notes' => $data['customer_notes'] ?? null,
                    'admin_notes' => $data['admin_notes'] ?? null,
                    'contact_status' => $data['contact_status'] ?? 'pending',
                    'coupon_code' => $data['coupon_code'] ?? null,
                ]);

                // Create order items
                foreach ($data['order_items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'] ?? $product->price,
                        'total_price' => ($item['unit_price'] ?? $product->price) * $item['quantity'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        // Item-level tax disabled – always zero
                        'tax_amount' => 0,
                        'product_attributes' => $item['product_attributes'] ?? [],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // Record coupon usage if coupon was used
                if (!empty($data['coupon_code']) && $totals['discount_amount'] > 0) {
                    $customer = Customer::findOrFail($data['customer_id']);
                    $coupon = Coupon::where('code', $data['coupon_code'])->first();
                    
                    if ($coupon) {
                        try {
                            $this->couponService->recordCouponUsage($coupon, $order, $customer, $totals['discount_amount']);
                        } catch (\Exception $e) {
                            Log::warning('فشل تسجيل استخدام الكوبون', [
                                'coupon_code' => $data['coupon_code'],
                                'order_id' => $order->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                Log::info('تم إنشاء طلب جديد', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_id' => $order->customer_id,
                    'user_id' => auth()->id(),
                ]);

                return $order->load(['customer', 'orderItems.product']);

            } catch (\Exception $e) {
                Log::error('خطأ في إنشاء الطلب', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update an order
     */
    public function updateOrder(Order $order, array $data)
    {
        return DB::transaction(function () use ($order, $data) {
            try {
                // If order items are being updated, recalculate totals
                if (isset($data['order_items'])) {
                    $totals = $this->calculateOrderTotals($data['order_items'], $data['coupon_code'] ?? $order->coupon_code);
                    
                    $order->update([
                        'subtotal' => $totals['subtotal'],
                        'tax_amount' => $totals['tax_amount'],
                        'shipping_amount' => $totals['shipping_amount'],
                        'discount_amount' => $totals['discount_amount'],
                        'total_amount' => $totals['total'],
                    ]);

                    // Delete old items and create new ones
                    $order->orderItems()->delete();
                    foreach ($data['order_items'] as $item) {
                        $product = Product::findOrFail($item['product_id']);
                        
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'product_sku' => $product->sku,
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'] ?? $product->price,
                            'total_price' => ($item['unit_price'] ?? $product->price) * $item['quantity'],
                            'discount_amount' => $item['discount_amount'] ?? 0,
                            'tax_amount' => 0,
                            'product_attributes' => $item['product_attributes'] ?? [],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }
                }

                // Update other fields
                $updateData = array_filter([
                    'status' => $data['status'] ?? null,
                    'payment_status' => $data['payment_status'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'shipping_address' => $data['shipping_address'] ?? null,
                    'billing_address' => $data['billing_address'] ?? null,
                    'shipping_method' => $data['shipping_method'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'customer_notes' => $data['customer_notes'] ?? null,
                    'admin_notes' => $data['admin_notes'] ?? null,
                    'contact_status' => $data['contact_status'] ?? null,
                    'coupon_code' => $data['coupon_code'] ?? null,
                    'tracking_number' => $data['tracking_number'] ?? null,
                ], function ($value) {
                    return $value !== null;
                });

                // Handle status changes and timestamps
                if (isset($data['status'])) {
                    switch ($data['status']) {
                        case Order::STATUS_CONFIRMED:
                            if (!$order->confirmed_at) {
                                $updateData['confirmed_at'] = now();
                            }
                            break;
                        case Order::STATUS_SHIPPED:
                            if (!$order->shipped_at) {
                                $updateData['shipped_at'] = now();
                            }
                            break;
                        case Order::STATUS_DELIVERED:
                            if (!$order->delivered_at) {
                                $updateData['delivered_at'] = now();
                            }
                            break;
                        case Order::STATUS_CANCELLED:
                            if (!$order->cancelled_at) {
                                $updateData['cancelled_at'] = now();
                            }
                            break;
                    }
                }

                // Handle contact status
                if (isset($data['contact_status']) && $data['contact_status'] === 'contacted' && !$order->contacted_at) {
                    $updateData['contacted_at'] = now();
                }

                $order->update($updateData);

                Log::info('تم تحديث الطلب', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => auth()->id(),
                ]);

                return $order->load(['customer', 'orderItems.product']);

            } catch (\Exception $e) {
                Log::error('خطأ في تحديث الطلب', [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                    'data' => $data,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Calculate order totals
     */
    public function calculateOrderTotals(array $items, ?string $couponCode = null): array
    {
        $subtotal = 0;
        $shippingAmount = 0; // Will be determined manually based on country

        // Calculate subtotal from items
        foreach ($items as $item) {
            $unitPrice = $item['unit_price'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $subtotal += $unitPrice * $quantity;
        }

        // Calculate discount from coupon using CouponService
        $discountAmount = 0;
        if ($couponCode) {
            $validation = $this->couponService->validateCoupon($couponCode, null, $subtotal);
            
            if ($validation['valid'] && $validation['coupon']) {
                $coupon = $validation['coupon'];
                $result = $this->couponService->applyCoupon($coupon, $subtotal, $items);
                $discountAmount = $result['discount_amount'];
                
                // Handle free shipping
                if ($coupon->type === 'free_shipping') {
                    $shippingAmount = 0; // Free shipping
                }
            }
        }

        // Tax disabled – always zero
        $taxAmount = 0;

        // Calculate total (subtotal - discount + shipping)
        $total = $subtotal - $discountAmount + $shippingAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'shipping_amount' => round($shippingAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Order $order, string $status): bool
    {
        try {
            $updateData = ['status' => $status];

            switch ($status) {
                case Order::STATUS_CONFIRMED:
                    if (!$order->confirmed_at) {
                        $updateData['confirmed_at'] = now();
                    }
                    break;
                case Order::STATUS_PROCESSING:
                    // No specific timestamp
                    break;
                case Order::STATUS_SHIPPED:
                    if (!$order->shipped_at) {
                        $updateData['shipped_at'] = now();
                    }
                    break;
                case Order::STATUS_DELIVERED:
                    if (!$order->delivered_at) {
                        $updateData['delivered_at'] = now();
                    }
                    break;
                case Order::STATUS_CANCELLED:
                    if (!$order->cancelled_at) {
                        $updateData['cancelled_at'] = now();
                    }
                    break;
            }

            $order->update($updateData);

            Log::info('تم تحديث حالة الطلب', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $order->getOriginal('status'),
                'new_status' => $status,
                'user_id' => auth()->id(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('خطأ في تحديث حالة الطلب', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'status' => $status,
                'user_id' => auth()->id(),
            ]);
            return false;
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Order $order, string $paymentStatus): bool
    {
        try {
            $order->update(['payment_status' => $paymentStatus]);

            Log::info('تم تحديث حالة الدفع', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $paymentStatus,
                'user_id' => auth()->id(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('خطأ في تحديث حالة الدفع', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'payment_status' => $paymentStatus,
                'user_id' => auth()->id(),
            ]);
            return false;
        }
    }

    /**
     * Update contact status
     */
    public function updateContactStatus(Order $order, string $contactStatus, ?string $adminNotes = null): bool
    {
        try {
            $updateData = ['contact_status' => $contactStatus];

            if ($contactStatus === 'contacted' && !$order->contacted_at) {
                $updateData['contacted_at'] = now();
            }

            if ($adminNotes !== null) {
                $updateData['admin_notes'] = $adminNotes;
            }

            $order->update($updateData);

            Log::info('تم تحديث حالة التواصل', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'contact_status' => $contactStatus,
                'user_id' => auth()->id(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('خطأ في تحديث حالة التواصل', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'contact_status' => $contactStatus,
                'user_id' => auth()->id(),
            ]);
            return false;
        }
    }

    /**
     * Get order statistics
     */
    public function getOrderStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', Order::STATUS_PENDING)->count(),
            'confirmed_orders' => Order::where('status', Order::STATUS_CONFIRMED)->count(),
            'shipped_orders' => Order::where('status', Order::STATUS_SHIPPED)->count(),
            'delivered_orders' => Order::where('status', Order::STATUS_DELIVERED)->count(),
            'cancelled_orders' => Order::where('status', Order::STATUS_CANCELLED)->count(),
            'today_orders' => Order::whereDate('created_at', $today)->count(),
            'month_orders' => Order::where('created_at', '>=', $thisMonth)->count(),
            'total_sales' => Order::where('status', '!=', Order::STATUS_CANCELLED)
                ->sum('total_amount'),
            'today_sales' => Order::whereDate('created_at', $today)
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->sum('total_amount'),
            'month_sales' => Order::where('created_at', '>=', $thisMonth)
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->sum('total_amount'),
            'pending_contact' => Order::where('contact_status', 'pending')->count(),
            'contacted' => Order::where('contact_status', 'contacted')->count(),
        ];
    }

    /**
     * Search orders
     */
    public function searchOrders(string $term)
    {
        return Order::with(['customer', 'orderItems.product'])
            ->where('order_number', 'like', "%{$term}%")
            ->orWhereHas('customer', function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                  ->orWhere('last_name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            })
            ->limit(20)
            ->get();
    }
}

