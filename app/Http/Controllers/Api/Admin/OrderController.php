<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * List orders for admin (with customer details for WhatsApp etc.).
     */
    public function index(Request $request): OrderCollection|JsonResponse
    {
        try {
            $orders = $this->orderService->listOrdersForAdmin();
            $orders->load(['customer', 'orderItems.product.productImages']);

            return new OrderCollection($orders);
        } catch (\Exception $e) {
            Log::error('Admin API: Error listing orders', ['error' => $e->getMessage()]);

            return ApiResponseHelper::error('ORDERS_ERROR', __('errors.server_error'), 500);
        }
    }

    /**
     * Show single order for admin.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'orderItems.product.productImages']);

        return ApiResponseHelper::success(new OrderResource($order));
    }

    /**
     * Update order status (mark as successful / paid or cancel).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->updateStatus($order, $request->validated('status'));
            $order->load(['customer', 'orderItems.product.productImages']);

            return ApiResponseHelper::success(new OrderResource($order), __('messages.order_updated'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponseHelper::error('INVALID_STATUS', $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Admin API: Error updating order status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return ApiResponseHelper::error('UPDATE_ORDER_ERROR', __('errors.server_error'), 500);
        }
    }
}
