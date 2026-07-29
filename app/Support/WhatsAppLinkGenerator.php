<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Order;

class WhatsAppLinkGenerator
{
    /**
     * Build wa.me link for contacting customer about an order.
     * Phone must be E.164 (e.g. +963912345678). Message is URL-encoded.
     */
    public static function forOrder(Order $order): ?string
    {
        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return null;
        }

        $phone = preg_replace('/\s+/', '', $customer->phone);
        if (!preg_match('/^\+?\d{10,15}$/', $phone)) {
            return null;
        }
        if (strpos($phone, '+') !== 0) {
            $phone = '+' . $phone;
        }

        $firstName = $customer->first_name ?? '';
        $lastName = $customer->last_name ?? '';
        $orderId = $order->id;
        $text = "Hello {$firstName} {$lastName}, we received your order #{$orderId} at Magic Show. We will contact you soon.";
        $encoded = rawurlencode($text);

        return "https://wa.me/{$phone}?text={$encoded}";
    }

    /**
     * Build wa.me link from explicit parameters (for use without loading relations).
     */
    public static function build(string $e164Phone, string $firstName, string $lastName, int $orderId): string
    {
        $phone = preg_replace('/\s+/', '', $e164Phone);
        if (strpos($phone, '+') !== 0) {
            $phone = '+' . $phone;
        }
        $text = "Hello {$firstName} {$lastName}, we received your order #{$orderId} at Magic Show. We will contact you soon.";
        $encoded = rawurlencode($text);

        return "https://wa.me/{$phone}?text={$encoded}";
    }
}
