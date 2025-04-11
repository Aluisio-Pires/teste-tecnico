<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->has(
            Order::factory()->count(5)->state([
                'status' => OrderStatus::REQUESTED,
            ])
        )->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
        ]);

        User::factory()->has(
            Order::factory()->count(5)->state([
                'status' => OrderStatus::REQUESTED,
            ])
        )->create([
            'name' => 'Regular User2',
            'email' => 'user2@example.com',
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ])->assignRole('admin');
    }
}
