<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_scope_with_status_filters_by_status(): void
    {
        Order::factory()->create(['status' => OrderStatus::REQUESTED->value]);
        Order::factory()->create(['status' => OrderStatus::APPROVED->value]);
        Order::factory()->create(['status' => OrderStatus::CANCELED->value]);

        $this->assertEquals(1, Order::withStatus(OrderStatus::REQUESTED->value)->count());
        $this->assertEquals(1, Order::withStatus(OrderStatus::APPROVED->value)->count());
        $this->assertEquals(1, Order::withStatus(OrderStatus::CANCELED->value)->count());
    }

    public function test_scope_in_date_range_filters_by_date_range(): void
    {
        Order::factory()->create([
            'departure_date' => '2025-01-01',
            'return_date' => '2025-01-10',
        ]);
        Order::factory()->create([
            'departure_date' => '2025-02-01',
            'return_date' => '2025-02-10',
        ]);

        $this->assertEquals(1, Order::inDateRange('2025-01-01', '2025-01-15')->count());
        $this->assertEquals(1, Order::inDateRange('2025-02-01', '2025-02-15')->count());
        $this->assertEquals(2, Order::inDateRange('2025-01-01', '2025-02-15')->count());
    }

    public function test_scope_in_date_range_returns_all_when_dates_not_provided(): void
    {
        Order::factory()->count(2)->create();

        $this->assertEquals(2, Order::inDateRange(null, null)->count());
    }

    public function test_scope_with_destination_filters_by_destination(): void
    {
        Order::factory()->create(['destination' => 'New York']);
        Order::factory()->create(['destination' => 'Paris']);

        $this->assertEquals(1, Order::withDestination('New York')->count());
        $this->assertEquals(1, Order::withDestination('Paris')->count());
        $this->assertEquals(0, Order::withDestination('Tokyo')->count());
    }

    public function test_scope_with_destination_returns_all_when_destination_not_provided(): void
    {
        Order::factory()->count(2)->create();

        $this->assertEquals(2, Order::withDestination(null)->count());
    }
}
