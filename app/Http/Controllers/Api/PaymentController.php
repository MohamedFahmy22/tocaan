<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\PaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Controller for payment management.
 *
 * Handles payment processing and retrieval.
 *
 * @group Payments
 */
class PaymentController extends Controller
{
    /**
     * @param PaymentService $paymentService Payment service
     * @param OrderService $orderService Order service
     */
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService
    ) {}

    /**
     * Get paginated list of payments.
     *
     * Retrieves all payments with optional filtering.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     *
     * @queryParam status string Filter by status (pending, successful, failed). Example: successful
     * @queryParam gateway string Filter by payment gateway. Example: credit_card
     * @queryParam per_page integer Items per page (default: 15). Example: 20
     * @queryParam order_id integer Filter by order ID. Example: 1
     *
     * @response 200 {
     *   "data": [{"id": 1, "payment_number": "PAY-ABC123", "status": "successful", "amount": 99.98}],
     *   "meta": {"current_page": 1, "per_page": 15, "total": 25}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->query('status'),
            'gateway' => $request->query('gateway'),
            'order_id' => $request->query('order_id'),
            'search' => $request->query('search'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $payments = $this->paymentService->getPayments($filters, $perPage);

        return PaymentResource::collection($payments);
    }

    /**
     * Get a single payment.
     *
     * Retrieves detailed information about a specific payment.
     *
     * @param int $id Payment ID
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {"id": 1, "payment_number": "PAY-ABC123", "status": "successful"}
     * }
     */
    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentService->getPayment($id);

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Get payments for a specific order.
     *
     * Retrieves all payments associated with an order.
     *
     * @param int $orderId Order ID
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": [{"id": 1, "payment_number": "PAY-ABC123", "status": "successful"}]
     * }
     */
    public function orderPayments(int $orderId): JsonResponse
    {
        // Verify order exists
        $this->orderService->getOrder($orderId);

        $payments = $this->paymentService->getOrderPayments($orderId);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
        ]);
    }

    /**
     * Process a payment for an order.
     *
     * Processes a payment using the specified payment method.
     * Business rule: Payments can only be processed for confirmed orders.
     *
     * @param ProcessPaymentRequest $request Validated payment data
     * @param int $orderId Order ID
     * @return JsonResponse
     *
     * @bodyParam payment_method string required Payment method to use. Example: credit_card
     * @bodyParam metadata object Optional metadata for the payment. Example: {"card_last_four": "4242"}
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Payment processed successfully",
     *   "data": {"id": 1, "payment_number": "PAY-ABC123", "status": "successful"}
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Payments can only be processed for confirmed orders"
     * }
     */
    public function processPayment(ProcessPaymentRequest $request, int $orderId): JsonResponse
    {
        $order = $this->orderService->getOrder($orderId);

        $dto = PaymentDTO::fromRequest(
            $request->validated(),
            $orderId,
            $this->paymentService->calculatePaymentAmount($order)
        );

        $payment = $this->paymentService->processPayment($order, $dto);

        $statusCode = $payment->isSuccessful()
            ? Response::HTTP_CREATED
            : Response::HTTP_PAYMENT_REQUIRED;

        return response()->json([
            'success' => $payment->isSuccessful(),
            'message' => $payment->isSuccessful()
                ? 'Payment processed successfully'
                : 'Payment processing failed',
            'data' => new PaymentResource($payment),
        ], $statusCode);
    }

    /**
     * Get available payment methods.
     *
     * Returns a list of all available payment gateways.
     *
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {"name": "credit_card", "display_name": "Credit Card", "enabled": true},
     *     {"name": "paypal", "display_name": "PayPal", "enabled": true}
     *   ]
     * }
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = $this->paymentService->getAvailablePaymentMethods();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }
}
