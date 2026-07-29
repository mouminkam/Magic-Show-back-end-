<?php

namespace App\Http\Resources;

use App\Support\WhatsAppLinkGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * Normalized status for frontend: pending, paid, cancelled. Orders are "Complete" when paid or when status is confirmed/delivered.
     */
    protected function normalizedStatus(): string
    {
        if ($this->payment_status === 'paid') {
            return 'paid';
        }
        if (in_array($this->status, ['confirmed', 'delivered'], true)) {
            return 'paid';
        }
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }
        return 'pending';
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->normalizedStatus(),
            'status_display' => $this->status_display_name,
            'payment_status' => $this->payment_status,
            'payment_status_display' => $this->payment_status_display_name,
            'contact_status' => $this->contact_status,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'total_price' => (float) $this->total_amount,
            'currency' => $this->currency,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'shipping_method' => $this->shipping_method,
            'customer_notes' => $this->customer_notes,
            'tracking_number' => $this->tracking_number,
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            // Null-safe: an order whose customer row was removed used to throw
            // inside CustomerResource instead of serialising as null.
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? new CustomerResource($this->customer) : null),
            'customer_full_name' => $this->when($this->relationLoaded('customer'), fn () => $this->customer ? trim($this->customer->first_name . ' ' . $this->customer->last_name) : null),
            'customer_phone_e164' => $this->when($this->relationLoaded('customer'), fn () => $this->customer?->phone),
            'whatsapp_link' => $this->when($this->relationLoaded('customer'), fn () => WhatsAppLinkGenerator::forOrder($this->resource)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'contacted_at' => $this->contacted_at?->toISOString(),
        ];
    }
}

