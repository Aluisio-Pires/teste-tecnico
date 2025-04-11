<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    /**
     * Create a new order.
     *
     * @param  array{destination: string, departure_date: string, return_date: string}  $data
     */
    public function create(array $data): Order
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Order $order */
        $order = $user->orders()->create([
            'destination' => $data['destination'],
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'status' => OrderStatus::REQUESTED,
        ]);

        return $order;
    }

    /**
     * Update order status.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $oldStatus = $order->status;
        $order->update([
            'status' => OrderStatus::from($status),
        ]);

        if (($order->status === OrderStatus::APPROVED || $order->status === OrderStatus::CANCELED) && $oldStatus !== $order->status) {
            $order->user->notify(new OrderStatusChanged($order));
        }

        return $order;
    }

    public function cancel(Order $order): bool
    {
        $oldStatus = $order->status;
        /** @var User $user */
        $user = auth()->user();

        if ($oldStatus === OrderStatus::APPROVED && ! $user->hasPermissionTo('delete-order')) {
            return false;
        }

        $order->update([
            'status' => OrderStatus::CANCELED,
        ]);

        if ($oldStatus !== OrderStatus::CANCELED) {
            $order->user->notify(new OrderStatusChanged($order));
        }

        return true;
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function list(?string $status = null, ?string $startDate = null, ?string $endDate = null, ?string $destination = null): LengthAwarePaginator
    {
        /** @var Builder<Order> $query */
        $query = Order::query();

        /** @var User $user */
        $user = auth()->user();

        if (! $user->hasPermissionTo('view-orders')) {
            $query->where('user_id', $user->id);
        }

        if ($status) {
            $query->withStatus($status);
        }

        if ($startDate && $endDate) {
            $query->inDateRange($startDate, $endDate);
        }

        if ($destination) {
            $query->withDestination($destination);
        }

        return $query->paginate();
    }
}
