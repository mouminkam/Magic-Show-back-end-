<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules. Frontend sends only cart_items and shipping.
     * No prices or customer names from client; backend computes and fetches from DB.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cart_items' => ['required', 'array', 'min:1', 'max:50'],
            'cart_items.*.product_id' => ['required', 'integer', 'min:1', 'exists:products,id'],
            'cart_items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'cart_items.*.size' => ['nullable', 'string', 'max:100'],
            'cart_items.*.color' => ['nullable', 'string', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'shipping' => ['required', 'array'],
            'shipping.address_line1' => ['required', 'string', 'max:255'],
            'shipping.city' => ['required', 'string', 'max:255'],
            'shipping.country' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cart_items' => __('orders.cart_items'),
            'shipping' => __('orders.shipping'),
        ];
    }
}
