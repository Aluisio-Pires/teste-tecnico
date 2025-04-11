<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\IndexOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    /**
     * Retorna uma lista de ordens de viagem paginada.
     *
     * @Request({
     *     tags: Pedido-de-Viagem
     * })
     */
    public function index(IndexOrderRequest $request): AnonymousResourceCollection
    {
        $orders = $this->orderService->list(
            $request->string('status'),
            $request->string('start_date'),
            $request->string('end_date'),
            $request->string('destination'),
        );

        return OrderResource::collection($orders);
    }

    /**
     * Cria uma ordem de viagem.
     *
     * @Request({
     *     tags: Pedido-de-Viagem
     * })
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        /** @var array{destination: string, departure_date: string, return_date: string} $validated */
        $validated = $request->validated();

        $order = $this->orderService->create($validated);

        return new OrderResource($order);
    }

    /**
     * Mostra os dados de uma Ordem de Viagem específica.
     *
     * @Request({
     *     tags: Pedido-de-Viagem
     * })
     */
    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order);
    }

    /**
     * Atualiza o status da ordem de viagem. ('requested', 'approved', 'canceled')
     *
     * @Request({
     *     tags: Pedido-de-Viagem
     * })
     *
     * @throws Exception
     */
    public function update(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        Gate::authorize('update', $order);

        $order = $this->orderService->updateStatus($order, $request->string('status'));

        return new OrderResource($order);
    }

    /**
     * Cancela uma ordem de viagem.
     *
     * @Request({
     *     tags: Pedido-de-Viagem
     * })
     */
    public function destroy(Order $order): JsonResponse
    {
        Gate::authorize('delete', $order);

        $result = $this->orderService->cancel($order);

        if (! $result) {
            return response()->json([
                'message' => __('This order cannot be canceled'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => __('Order canceled successfully'),
            'data' => new OrderResource($order),
        ]);
    }
}
