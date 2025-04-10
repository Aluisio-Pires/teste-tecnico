<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        /** @var User $user */
        $user = $order->user;

        return [
            'id' => $order->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'destination' => $order->destination,
            'departure_date' => $order->departure_date instanceof Carbon
                ? $order->departure_date->format('d/m/Y')
                : $order->departure_date,
            'return_date' => $order->return_date instanceof Carbon
                ? $order->return_date->format('d/m/Y')
                : $order->return_date,
            'status' => $order->status,
            'created_at' => $order->created_at->format('d/m/Y H:i:s'),
            'updated_at' => $order->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}
