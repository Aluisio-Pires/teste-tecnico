<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('view-orders') || $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('update-order') && $order->user_id !== $user->id;
    }

    /**
     * Determine whether the user can delete/cancel the order.
     */
    public function delete(User $user, Order $order): bool
    {
        return ($user->id === $order->user_id || $user->hasPermissionTo('delete-order'))
            && $order->status !== OrderStatus::CANCELED;
    }
}
