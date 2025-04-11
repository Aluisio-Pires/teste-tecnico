<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Order>
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'destination' => $this->faker->city(),
            'departure_date' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'return_date' => $this->faker->dateTimeBetween('+3 weeks', '+4 weeks'),
            'status' => $this->faker->randomElement(OrderStatus::values()),
        ];
    }

    public function requested(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::REQUESTED->value,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::APPROVED->value,
        ]);
    }

    public function canceled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELED->value,
        ]);
    }
}
