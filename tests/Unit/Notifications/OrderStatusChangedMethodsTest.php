<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

final class OrderStatusChangedMethodsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Order $approvedOrder;

    private Order $canceledOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

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

    public function test_to_mail_method_for_approved_order()
    {
        $notification = new OrderStatusChanged($this->approvedOrder);
        $mailMessage = $notification->toMail($this->user);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals(__('Travel Order Status Update').': '.__($this->approvedOrder->status->value), $mailMessage->subject);

        $mailArray = $mailMessage->toArray();
        $this->assertEquals(__('Hello')." {$this->user->name}!", $mailArray['greeting']);
        $this->assertContains(__('Your travel order has been approved'), $mailArray['introLines']);
        $this->assertContains(__('Destination').": {$this->approvedOrder->destination}", $mailArray['introLines']);
        $this->assertContains(__('Departure Date').": {$this->approvedOrder->departure_date->format('d/m/Y')}", $mailArray['introLines']);
        $this->assertContains(__('Return Date').": {$this->approvedOrder->return_date->format('d/m/Y')}", $mailArray['introLines']);
    }

    public function test_to_mail_method_for_canceled_order()
    {
        $notification = new OrderStatusChanged($this->canceledOrder);
        $mailMessage = $notification->toMail($this->user);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals(__('Travel Order Status Update').': '.__($this->canceledOrder->status->value), $mailMessage->subject);

        $mailArray = $mailMessage->toArray();
        $this->assertEquals(__('Hello')." {$this->user->name}!", $mailArray['greeting']);
        $this->assertContains(__('Your travel order has been canceled'), $mailArray['introLines']);
        $this->assertContains(__('Destination').": {$this->canceledOrder->destination}", $mailArray['introLines']);
        $this->assertContains(__('Departure Date').": {$this->canceledOrder->departure_date->format('d/m/Y')}", $mailArray['introLines']);
        $this->assertContains(__('Return Date').": {$this->canceledOrder->return_date->format('d/m/Y')}", $mailArray['introLines']);
    }

    public function test_to_array_method_for_approved_order()
    {
        $notification = new OrderStatusChanged($this->approvedOrder);
        $array = $notification->toArray($this->user);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('order_id', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('destination', $array);
        $this->assertArrayHasKey('departure_date', $array);
        $this->assertArrayHasKey('return_date', $array);

        $this->assertEquals($this->approvedOrder->id, $array['order_id']);
        $this->assertEquals($this->approvedOrder->status, $array['status']);
        $this->assertEquals($this->approvedOrder->destination, $array['destination']);
        $this->assertEquals($this->approvedOrder->departure_date->format('d/m/Y'), $array['departure_date']);
        $this->assertEquals($this->approvedOrder->return_date->format('d/m/Y'), $array['return_date']);
    }

    public function test_to_array_method_for_canceled_order()
    {
        $notification = new OrderStatusChanged($this->canceledOrder);
        $array = $notification->toArray($this->user);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('order_id', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('destination', $array);
        $this->assertArrayHasKey('departure_date', $array);
        $this->assertArrayHasKey('return_date', $array);

        $this->assertEquals($this->canceledOrder->id, $array['order_id']);
        $this->assertEquals($this->canceledOrder->status, $array['status']);
        $this->assertEquals($this->canceledOrder->destination, $array['destination']);
        $this->assertEquals($this->canceledOrder->departure_date->format('d/m/Y'), $array['departure_date']);
        $this->assertEquals($this->canceledOrder->return_date->format('d/m/Y'), $array['return_date']);
    }

    public function test_via_method()
    {
        $notification = new OrderStatusChanged($this->approvedOrder);
        $channels = $notification->via($this->user);

        $this->assertEquals(['mail'], $channels);
    }
}
