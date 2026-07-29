<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Support\ProductImageUrlBuilder;
use Illuminate\Http\Request;
use RuntimeException;

class CartService
{
    /**
     * Hard ceiling on the quantity of a single cart line. Mirrors the
     * `max:100` rule on AddToCartRequest/UpdateCartItemRequest so that
     * repeatedly adding the same product cannot grow past the validated bound.
     */
    public const MAX_LINE_QUANTITY = 100;

    /**
     * Resolve the cart for the current user or guest session.
     * Creates the cart if it does not exist yet.
     */
    public function getOrCreateCart(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::firstOrCreate(['customer_id' => $request->user()->id]);
        }

        // Defensive: the cart routes start a session, but if this service is
        // ever called from a genuinely stateless context we return a detached
        // (unsaved) cart rather than throwing "Session store not set".
        if (!$request->hasSession()) {
            return new Cart();
        }

        return Cart::firstOrCreate(['session_id' => $request->session()->getId()]);
    }

    /**
     * Return a mapped array of cart items ready for API responses.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function mapCartItems(Cart $cart): \Illuminate\Support\Collection
    {
        return $cart->items()
            ->with('product.productImages')
            ->get()
            ->map(function (CartItem $item) {
                $product = $item->product;
                $imageUrls = $product ? ProductImageUrlBuilder::build($product) : null;

                return [
                    'id'        => $item->id,
                    'productId' => $item->product_id,
                    'name'      => $product?->name,
                    'price'     => $item->price,
                    'quantity'  => $item->quantity,
                    'image'     => $imageUrls['thumb'] ?? ($product?->primary_image_thumb_url ?? $product?->primary_image_url),
                    'image_urls' => $imageUrls,
                    'size'      => $item->size,
                    'color'     => $item->color,
                    'subtotal'  => $item->price * $item->quantity,
                ];
            });
    }

    /**
     * Add a product to the cart, or increment the quantity of an existing line.
     *
     * @throws RuntimeException when the product is not found or not active
     */
    public function addItem(Cart $cart, int $productId, int $quantity, ?string $size, ?string $color): CartItem
    {
        $product = Product::find($productId);

        if (!$product) {
            throw new RuntimeException('product_not_found');
        }

        if (!$product->is_active) {
            throw new RuntimeException('product_inactive');
        }

        if (!$cart->exists) {
            throw new RuntimeException('cart_unavailable');
        }

        $existing = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('size', $size)
            ->where('color', $color)
            ->first();

        if ($existing) {
            // Clamp instead of unbounded accumulation: without this a client can
            // bypass the `max:100` request rule by POSTing the same line repeatedly.
            $existing->quantity = min($existing->quantity + $quantity, self::MAX_LINE_QUANTITY);
            $existing->save();
            return $existing;
        }

        return CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'price'      => $product->sale_price ?? $product->price,
            'size'       => $size,
            'color'      => $color,
        ]);
    }

    /**
     * Validate a coupon code and return its details.
     *
     * @return array<string, mixed>
     * @throws RuntimeException when the coupon is invalid or exhausted
     */
    public function validateCoupon(string $code): array
    {
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (!$coupon) {
            throw new RuntimeException('invalid_coupon');
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw new RuntimeException('coupon_expired');
        }

        return [
            'valid'          => true,
            'coupon_code'    => $coupon->code,
            'discount_type'  => $coupon->type,
            'discount_value' => $coupon->value,
            'description'    => $coupon->description,
            'expires_at'     => $coupon->expires_at,
        ];
    }
}
