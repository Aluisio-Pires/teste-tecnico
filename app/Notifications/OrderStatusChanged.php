<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $statusMessage = $this->order->status === OrderStatus::APPROVED
            ? __('Your travel order has been approved')
            : __('Your travel order has been canceled');

        return (new MailMessage)
            ->subject(__('Travel Order Status Update').': '.__($this->order->status->value))
            ->greeting(__('Hello')." {$notifiable->name}!")
            ->line($statusMessage)
            ->line(__('Destination').": {$this->order->destination}")
            ->line(__('Departure Date').": {$this->order->departure_date->format('d/m/Y')}")
            ->line(__('Return Date').": {$this->order->return_date->format('d/m/Y')}")
            ->line(__('Thank you for using our application!'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'destination' => $this->order->destination,
            'departure_date' => $this->order->departure_date->format('d/m/Y'),
            'return_date' => $this->order->return_date->format('d/m/Y'),
        ];
    }
}
