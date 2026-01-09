<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\OrderDTO;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Controller for order management.
 *
 * Handles CRUD operations for orders and status transitions.
 *
 * @group Orders
 */
class OrderController extends Controller
{
    /**
     * @param OrderService $orderService Order service
     */
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Get paginated list of orders.
     *
     * Retrieves all orders with optional filtering by status, date range, etc.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     *
     * @queryParam status string Filter by status (pending, confirmed, cancelled). Example: confirmed
     * @queryParam per_page integer Items per page (default: 15). Example: 20
     * @queryParam search string Search by order number or user name. Example: ORD-
     * @queryParam from_date date Filter orders from this date. Example: 2024-01-01
     * @queryParam to_date date Filter orders until this date. Example: 2024-12-31
     *
     * @response 200 {
     *   "data": [{"id": 1, "order_number": "ORD-ABC123", "status": "pending", "total_amount": 299.98}],
     *   "meta": {"current_page": 1, "per_page": 15, "total": 50}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'user_id' => $request->query('user_id'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $orders = $this->orderService->getOrders($filters, $perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Get a single order.
     *
     * Retrieves detailed information about a specific order.
     *
     * @param int $id Order ID
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {"id": 1, "order_number": "ORD-ABC123", "status": "pending", "items": [...]}
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Order with ID 999 not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Create a new order.
     *
     * Creates a new order with the provided items.
     *
     * @param CreateOrderRequest $request Validated order data
     * @return JsonResponse
     *
     * @bodyParam items array required List of order items. Example: [{"product_name": "Product A", "quantity": 2, "unit_price": 49.99}]
     * @bodyParam notes string Optional order notes. Example: Please deliver ASAP
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Order created successfully",
     *   "data": {"id": 1, "order_number": "ORD-ABC123", "status": "pending", "total_amount": 99.98}
     * }
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $dto = OrderDTO::fromRequest(
            $request->validated(),
            auth()->id()
        );

        $order = $this->orderService->createOrder($dto);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => new OrderResource($order),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing order.
     *
     * Updates an order's notes and/or items. Only pending orders can be updated.
     *
     * @param UpdateOrderRequest $request Validated update data
     * @param int $id Order ID
     * @return JsonResponse
     *
     * @bodyParam notes string Optional order notes. Example: Updated delivery instructions
     * @bodyParam items array Optional new list of items. Example: [{"product_name": "Product B", "quantity": 1, "unit_price": 79.99}]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Order updated successfully",
     *   "data": {"id": 1, "order_number": "ORD-ABC123", "status": "pending"}
     * }
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);

        $dto = OrderDTO::forUpdate(
            $request->validated(),
            $order->id,
            $order->user_id,
            $order->order_number,
            $order->status
        );

        $updatedOrder = $this->orderService->updateOrder($id, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => new OrderResource($updatedOrder),
        ]);
    }

    /**
     * Delete an order.
     *
     * Deletes an order. Orders with associated payments cannot be deleted.
     *
     * @param int $id Order ID
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Order deleted successfully"
     * }
     * @response 409 {
     *   "success": false,
     *   "message": "Cannot delete order with associated payments"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $this->orderService->deleteOrder($id);

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully',
        ]);
    }

    /**
     * Confirm an order.
     *
     * Transitions an order from pending to confirmed status.
     *
     * @param int $id Order ID
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Order confirmed successfully",
     *   "data": {"id": 1, "status": "confirmed"}
     * }
     */
    public function confirm(int $id): JsonResponse
    {
        $order = $this->orderService->confirmOrder($id);

        return response()->json([
            'success' => true,
            'message' => 'Order confirmed successfully',
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Cancel an order.
     *
     * Cancels an order. Orders with successful payments cannot be cancelled.
     *
     * @param int $id Order ID
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Order cancelled successfully",
     *   "data": {"id": 1, "status": "cancelled"}
     * }
     */
    public function cancel(int $id): JsonResponse
    {
        $order = $this->orderService->cancelOrder($id);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Get orders for the authenticated user.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function myOrders(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 15);
        $orders = $this->orderService->getUserOrders(auth()->id(), $perPage);

        return OrderResource::collection($orders);
    }
}
