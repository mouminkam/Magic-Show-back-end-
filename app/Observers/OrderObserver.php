<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\FirebaseNotificationService;

class OrderObserver
{
    public function __construct(
        protected FirebaseNotificationService $firebase
    ) {
    }

    /**
     * When a new order is created, notify the admin dashboard via Firebase (real-time).
     */
    public function created(Order $order): void
    {
        if (!$this->firebase->isConfigured()) {
            return;
        }
        $this->firebase->notifyNewOrder($order);
    }
}
