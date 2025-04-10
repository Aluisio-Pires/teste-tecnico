<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_index_returns_all_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        $this->authRequest(
            'get',
            route('api.orders.index'),
            Response::HTTP_OK,
            [],
            $user
        )->assertJsonCount(3, 'data');
    }

    public function test_index_filters_by_status(): void
    {
        $user = User::factory()->create();

        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::REQUESTED]);
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::APPROVED]);

        $this->authRequest(
            'get',
            route('api.orders.index', ['status' => OrderStatus::APPROVED]),
            Response::HTTP_OK,
            [],
            $user
        )->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', OrderStatus::APPROVED->value);
    }

    public function test_index_filters_by_date_range(): void
    {
        $user = User::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'departure_date' => '2025-01-01',
            'return_date' => '2025-01-10',
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'departure_date' => '2025-02-01',
            'return_date' => '2025-02-10',
        ]);

        $this->authRequest(
            'get',
            route('api.orders.index', ['start_date' => '2025-01-01', 'end_date' => '2025-01-15']),
            Response::HTTP_OK,
            [],
            $user
        )->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_destination(): void
    {
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'destination' => 'New York',
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'destination' => 'Paris',
        ]);

        $this->authRequest(
            'get',
            route('api.orders.index', ['destination' => 'New York']),
            Response::HTTP_OK,
            [],
            $user
        )->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.destination', 'New York');
    }

    public function test_store_creates_new_order(): void
    {
        $user = User::factory()->create();
        $request = Order::factory()->make(['user_id' => $user->id]);

        $this->authRequest(
            'post',
            route('api.orders.store'),
            Response::HTTP_CREATED,
            $request->toArray(),
            $user
        )->assertJsonPath('data.destination', $request->destination)
            ->assertJsonPath('data.departure_date', (string) $request->departure_date->format('d/m/Y'))
            ->assertJsonPath('data.return_date', (string) $request->return_date->format('d/m/Y'))
            ->assertJsonPath('data.status', OrderStatus::REQUESTED->value)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'destination' => $request->destination,
            'status' => OrderStatus::REQUESTED,
        ]);
    }

    public function test_store_validates_input(): void
    {
        $this->authRequest(
            'post',
            route('api.orders.store'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            [
                'destination' => '',
                'departure_date' => 'invalid-date',
                'return_date' => '2025-01-01',
            ]
        )->assertJsonValidationErrors(['destination', 'departure_date']);
    }

    public function test_show_returns_order_details(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->authRequest(
            'get',
            route('api.orders.show', $order),
            Response::HTTP_OK,
            [],
            $user
        )->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.destination', $order->destination)
            ->assertJsonPath('data.status', $order->status->value);
    }

    /**
     * @throws Exception
     */
    public function test_update_status_changes_order_status(): void
    {
        $authUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $targetUser->id,
            'status' => OrderStatus::REQUESTED->value,
        ]);

        $this->authRequest(
            'patch',
            route('api.orders.show', $order),
            Response::HTTP_OK,
            [
                'status' => OrderStatus::APPROVED->value,
            ],
            $authUser
        )->assertJsonPath('data.status', OrderStatus::APPROVED->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::APPROVED->value,
        ]);

        Notification::assertSentTo($targetUser, OrderStatusChanged::class);
    }

    public function test_update_status_validates_status_value(): void
    {
        $authUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $targetUser->id,
        ]);

        $this->authRequest(
            'patch',
            route('api.orders.show', $order),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            [
                'status' => 'invalid-status',
            ],
            $authUser
        )->assertJsonValidationErrors(['status']);
    }

    public function test_update_status_prevents_owner_from_updating_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->authRequest(
            'patch',
            route('api.orders.show', $order),
            Response::HTTP_FORBIDDEN,
            [
                'status' => OrderStatus::APPROVED->value,
            ],
            $user
        );
    }

    /**
     * @throws Exception
     */
    public function test_cancel_cancels_approved_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::APPROVED->value,
        ]);

        $this->authRequest(
            'post',
            route('api.orders.destroy', $order),
            Response::HTTP_OK,
            [],
            $user
        )->assertJsonPath('data.status', OrderStatus::CANCELED->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELED->value,
        ]);

        Notification::assertSentTo($user, OrderStatusChanged::class);
    }

    public function test_cancel_returns_error_when_already_canceled(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::CANCELED->value,
        ]);

        $this->authRequest(
            'post',
            route('api.orders.destroy', $order),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            [],
            $user
        )->assertJsonPath('message', __('This order cannot be canceled'));
    }

    public function test_unauthorized_access_is_rejected(): void
    {
        $this->isProtected('get', route('api.orders.index'));
    }
}
