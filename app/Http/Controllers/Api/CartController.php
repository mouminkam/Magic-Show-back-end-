<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\CheckoutRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Requests\Cart\ValidateCouponRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService,
    ) {}

    /**
     * Get cart items.
     */
    public function index(Request $request)
    {
        $cart  = $this->cartService->getOrCreateCart($request);
        $items = $this->cartService->mapCartItems($cart);

        return ApiResponseHelper::success([
            'items'     => $items,
            'total'     => $items->sum('subtotal'),
            'itemCount' => $items->count(),
        ]);
    }

    /**
     * Add item to cart.
     */
    public function store(AddToCartRequest $request)
    {
        try {
            $validated = $request->validated();
            $cart      = $this->cartService->getOrCreateCart($request);

            $item = $this->cartService->addItem(
                $cart,
                $validated['product_id'],
                $validated['quantity'],
                $validated['size'] ?? null,
                $validated['color'] ?? null,
            );

            return ApiResponseHelper::success($item, __('messages.item_added_to_cart'));

        } catch (\RuntimeException $e) {
            $code = match ($e->getMessage()) {
                'product_not_found' => 'PRODUCT_NOT_FOUND',
                'product_inactive'  => 'PRODUCT_INACTIVE',
                default             => 'ADD_TO_CART_ERROR',
            };
            $status = $e->getMessage() === 'product_not_found' ? 404 : 400;
            return ApiResponseHelper::error($code, __('errors.' . $e->getMessage(), [], __('errors.server_error')), $status);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('ADD_TO_CART_ERROR', __('errors.server_error'), 500);
        }
    }

    /**
     * Update cart item quantity.
     */
    public function update(UpdateCartItemRequest $request, $itemId)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        $item->update(['quantity' => $request->validated()['quantity']]);

        return ApiResponseHelper::success($item, __('messages.cart_updated'));
    }

    /**
     * Remove item from cart.
     */
    public function destroy(Request $request, $itemId)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        $item->delete();

        return ApiResponseHelper::success(null, __('messages.item_removed'));
    }

    /**
     * Clear all cart items.
     */
    public function clear(Request $request)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $cart->items()->delete();

        return ApiResponseHelper::success(null, __('messages.cart_cleared'));
    }

    /**
     * Validate a coupon code.
     */
    public function validateCoupon(ValidateCouponRequest $request)
    {
        try {
            $result = $this->cartService->validateCoupon($request->validated()['coupon_code']);
            return ApiResponseHelper::success($result);
        } catch (\RuntimeException $e) {
            $code = $e->getMessage() === 'coupon_expired' ? 'COUPON_EXPIRED' : 'INVALID_COUPON';
            $msg  = $e->getMessage() === 'coupon_expired'
                ? __('messages.coupon_expired')
                : __('messages.invalid_coupon');
            return ApiResponseHelper::error($code, $msg, 400);
        }
    }

    /**
     * Checkout and create order.
     */
    public function checkout(CheckoutRequest $request)
    {
        try {
            $validated = $request->validated();
            $order     = $this->orderService->createFromCart($validated, $request->user()->id);

            Cart::where('customer_id', $request->user()->id)->first()?->items()->delete();

            return ApiResponseHelper::success([
                'order_id'           => $order->id,
                'order_number'       => $order->order_number,
                'total'              => $order->total_amount,
                'subtotal'           => $order->subtotal,
                'tax'                => $order->tax_amount,
                'discount'           => $order->discount_amount,
                'status'             => $order->status,
                'created_at'         => $order->created_at,
                'estimated_delivery' => now()->addDays(5),
            ], __('messages.order_placed'));

        } catch (\RuntimeException $e) {
            $msg  = $e->getMessage();
            $code = str_contains(strtolower($msg), 'insufficient') ? 'INSUFFICIENT_STOCK'
                : (str_contains(strtolower($msg), 'coupon') ? 'COUPON_INVALID' : 'CHECKOUT_ERROR');
            return ApiResponseHelper::error($code, $msg, 422);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('CHECKOUT_ERROR', __('errors.server_error'), 500);
        }
    }
}
