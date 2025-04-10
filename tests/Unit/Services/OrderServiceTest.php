<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService;
        Notification::fake();
    }

    public function test_create_order_creates_new_order_with_correct_data(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        $orderData = [
            'destination' => 'New York',
            'departure_date' => '2025-05-01',
            'return_date' => '2025-05-10',
        ];

        $order = $this->orderService->create($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals('New York', $order->destination);
        $this->assertEquals('2025-05-01', $order->departure_date->format('Y-m-d'));
        $this->assertEquals('2025-05-10', $order->return_date->format('Y-m-d'));
        $this->assertEquals(OrderStatus::REQUESTED->value, $order->status->value);
    }

    public function test_update_order_status_updates_status_and_sends_notification(): void
    {
        $owner = User::factory()->create();
        $updater = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => OrderStatus::REQUESTED->value,
        ]);

        Auth::shouldReceive('user')->andReturn($updater);

        $updatedOrder = $this->orderService->updateStatus($order, OrderStatus::APPROVED->value);

        $this->assertEquals(OrderStatus::APPROVED->value, $updatedOrder->status->value);
        Notification::assertSentTo($owner, OrderStatusChanged::class);
    }

    public function test_update_order_status_does_not_send_notification_when_status_not_changed(): void
    {
        $owner = User::factory()->create();
        $updater = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => OrderStatus::APPROVED->value,
        ]);

        Auth::shouldReceive('user')->andReturn($updater);

        $updatedOrder = $this->orderService->updateStatus($order, OrderStatus::APPROVED->value);

        $this->assertEquals(OrderStatus::APPROVED->value, $updatedOrder->status->value);
        Notification::assertNotSentTo($owner, OrderStatusChanged::class);
    }

    public function test_cancel_order_returns_true_and_updates_status_when_cancellable(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => OrderStatus::APPROVED->value,
        ]);

        $result = $this->orderService->cancel($order);

        $this->assertTrue($result);
        $this->assertEquals(OrderStatus::CANCELED->value, $order->status->value);
        Notification::assertSentTo($owner, OrderStatusChanged::class);
    }

    public function test_list_orders_returns_all_orders_when_no_filters(): void
    {
        Order::factory()->count(3)->create();

        $orders = $this->orderService->list();

        $this->assertCount(3, $orders);
    }

    public function test_list_orders_filters_by_status(): void
    {
        Order::factory()->create(['status' => OrderStatus::REQUESTED->value]);
        Order::factory()->create(['status' => OrderStatus::APPROVED->value]);
        Order::factory()->create(['status' => OrderStatus::CANCELED->value]);

        $orders = $this->orderService->list(OrderStatus::APPROVED->value);

        $this->assertCount(1, $orders);
        $this->assertEquals(OrderStatus::APPROVED->value, $orders->first()->status->value);
    }

    public function test_list_orders_filters_by_date_range(): void
    {
        Order::factory()->create([
            'departure_date' => '2025-01-01',
            'return_date' => '2025-01-10',
        ]);
        Order::factory()->create([
            'departure_date' => '2025-02-01',
            'return_date' => '2025-02-10',
        ]);

        $orders = $this->orderService->list(null, '2025-01-01', '2025-01-15');

        $this->assertCount(1, $orders);
        $this->assertEquals('2025-01-01', $orders->first()->departure_date->format('Y-m-d'));
    }

    public function test_list_orders_filters_by_destination(): void
    {
        Order::factory()->create(['destination' => 'New York']);
        Order::factory()->create(['destination' => 'Paris']);

        $orders = $this->orderService->list(null, null, null, 'Paris');

        $this->assertCount(1, $orders);
        $this->assertEquals('Paris', $orders->first()->destination);
    }
}
