<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class OrderStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Order $approvedOrder;

    private Order $canceledOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->approvedOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'destination' => 'Paris',
            'departure_date' => '2025-05-01',
            'return_date' => '2025-05-15',
            'status' => OrderStatus::APPROVED,
        ]);

        $this->canceledOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'destination' => 'Tokyo',
            'departure_date' => '2025-06-01',
            'return_date' => '2025-06-15',
            'status' => OrderStatus::CANCELED,
        ]);
    }

    public function it_sends_notification_through_mail_channel()
    {
        $notification = new OrderStatusChanged($this->approvedOrder);
        $channels = $notification->via($this->user);

        $this->assertEquals(['mail'], $channels);
    }

    public function it_builds_mail_message_for_approved_order()
    {
        $notification = new OrderStatusChanged($this->approvedOrder);
        $mailMessage = $notification->toMail($this->user);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals("Travel Order Status Update: {$this->approvedOrder->status->value}", $mailMessage->subject);

        $mailMessageData = $mailMessage->toArray();
        $this->assertStringContainsString('Hello Test User!', $mailMessageData['greeting']);
        $this->assertStringContainsString('Your travel order has been approved', $mailMessageData['introLines'][0]);
        $this->assertStringContainsString("Destination: {$this->approvedOrder->destination}", $mailMessageData['introLines'][1]);
        $this->assertStringContainsString("Departure Date: {$this->approvedOrder->departure_date->format('Y-m-d')}", $mailMessageData['introLines'][2]);
        $this->assertStringContainsString("Return Date: {$this->approvedOrder->return_date->format('Y-m-d')}", $mailMessageData['introLines'][3]);
    }

    public function it_builds_mail_message_for_canceled_order()
    {
        $notification = new OrderStatusChanged($this->canceledOrder);
        $mailMessage = $notification->toMail($this->user);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals("Travel Order Status Update: {$this->canceledOrder->status->value}", $mailMessage->subject);

        $mailMessageData = $mailMessage->toArray();
        $this->assertStringContainsString('Hello Test User!', $mailMessageData['greeting']);
        $this->assertStringContainsString('Your travel order has been canceled', $mailMessageData['introLines'][0]);
        $this->assertStringContainsString("Destination: {$this->canceledOrder->destination}", $mailMessageData['introLines'][1]);
        $this->assertStringContainsString("Departure Date: {$this->canceledOrder->departure_date->format('Y-m-d')}", $mailMessageData['introLines'][2]);
        $this->assertStringContainsString("Return Date: {$this->canceledOrder->return_date->format('Y-m-d')}", $mailMessageData['introLines'][3]);
    }

    public function it_returns_correct_array_representation()
    {
        $notification = new OrderStatusChanged($this->approvedOrder);
        $array = $notification->toArray($this->user);

        $this->assertEquals([
            'order_id' => $this->approvedOrder->id,
            'status' => $this->approvedOrder->status,
            'destination' => $this->approvedOrder->destination,
            'departure_date' => $this->approvedOrder->departure_date->format('Y-m-d'),
            'return_date' => $this->approvedOrder->return_date->format('Y-m-d'),
        ], $array);
    }

    public function it_returns_correct_array_representation_for_canceled_order()
    {
        $notification = new OrderStatusChanged($this->canceledOrder);
        $array = $notification->toArray($this->user);

        $this->assertEquals([
            'order_id' => $this->canceledOrder->id,
            'status' => $this->canceledOrder->status,
            'destination' => $this->canceledOrder->destination,
            'departure_date' => $this->canceledOrder->departure_date->format('Y-m-d'),
            'return_date' => $this->canceledOrder->return_date->format('Y-m-d'),
        ], $array);
    }

    public function test_mail_message_has_correct_structure()
    {
        $notification = new OrderStatusChanged($this->approvedOrder);
        $mailMessage = $notification->toMail($this->user);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertNotEmpty($mailMessage->subject);
        $this->assertNotEmpty($mailMessage->greeting);

        $mailArray = $mailMessage->toArray();
        $this->assertArrayHasKey('introLines', $mailArray);
        $this->assertCount(5, $mailArray['introLines']);
    }

    /**
     * @throws Exception
     */
    public function it_sends_notification_when_order_status_changes_to_approved()
    {
        Notification::fake();

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::REQUESTED,
        ]);

        $order->update([
            'status' => OrderStatus::APPROVED,
        ]);

        $this->user->notify(new OrderStatusChanged($order));

        Notification::assertSentTo(
            $this->user,
            OrderStatusChanged::class,
            function ($notification, $channels) use ($order) {
                return $notification->order->id === $order->id &&
                       $notification->order->status === OrderStatus::APPROVED;
            }
        );
    }

    /**
     * @throws Exception
     */
    public function it_sends_notification_when_order_status_changes_to_canceled()
    {
        Notification::fake();

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::REQUESTED,
        ]);

        $order->update([
            'status' => OrderStatus::CANCELED,
        ]);

        $this->user->notify(new OrderStatusChanged($order));

        Notification::assertSentTo(
            $this->user,
            OrderStatusChanged::class,
            function ($notification, $channels) use ($order) {
                return $notification->order->id === $order->id &&
                    $notification->order->status === OrderStatus::CANCELED;
            }
        );
    }
}
