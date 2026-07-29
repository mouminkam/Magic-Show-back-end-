<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
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
     * Get authenticated customer's orders (order history).
     */
    public function index(Request $request): OrderCollection|JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $page = (int) $request->get('page', 1);
            $orders = $this->orderService->listOrdersForCustomer($customerId, $page);
            $orders->load(['orderItems.product', 'orderItems.product.productImages']);

            return new OrderCollection($orders);
        } catch (\Exception $e) {
            Log::error('Error fetching orders via API', [
                'error' => $e->getMessage(),
                'customer_id' => $request->user()->id,
            ]);

            return ApiResponseHelper::error(
                'ORDERS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Create a new order (cart_items + shipping only; backend authoritative).
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->placeOrder($request->validated());
            $order->load(['orderItems.product', 'orderItems.product.productImages']);

            Log::info('Order created via API', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
            ]);

            return ApiResponseHelper::created(
                new OrderResource($order),
                __('messages.order_placed')
            );
        } catch (\RuntimeException $e) {
            return ApiResponseHelper::error(
                'CREATE_ORDER_ERROR',
                $e->getMessage(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Error creating order via API', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'customer_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $message = __('errors.server_error');
            if (config('app.debug')) {
                $message = $e->getMessage();
            }

            return ApiResponseHelper::error(
                'CREATE_ORDER_ERROR',
                $message,
                500
            );
        }
    }

    /**
     * Get a specific order
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        try {
            // Ensure the order belongs to the authenticated customer
            if ($order->customer_id !== $request->user()->id) {
                return ApiResponseHelper::forbidden(
                    'You do not have permission to access this order'
                );
            }
            
            $order->load(['customer', 'orderItems.product', 'orderItems.product.productImages']);
            
            return ApiResponseHelper::success(
                new OrderResource($order)
            );
            
        } catch (\Exception $e) {
            Log::error('Error fetching order via API', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'customer_id' => $request->user()->id,
            ]);
            
            return ApiResponseHelper::error(
                'ORDER_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }
}

