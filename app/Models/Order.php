<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property-read int $user_id
 * @property-read string $destination
 * @property-read Carbon $departure_date
 * @property-read Carbon $return_date
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read OrderStatus $status
 * @property-read User $user
 */
final class Order extends Model
{
    /** @use HasFactory<Factory<Order>> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'destination',
        'departure_date',
        'return_date',
        'status',
    ];

    /**
     * Get the user that owns the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'status' => OrderStatus::class,
        ];
    }

    /**
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function inDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate && $endDate) {
            $query->whereBetween('departure_date', [$startDate, $endDate])
                ->orWhereBetween('return_date', [$startDate, $endDate]);
        }
    }

    /**
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function withDestination(Builder $query, ?string $destination): void
    {
        if ($destination) {
            $query->where('destination', 'like', "%{$destination}%");
        }
    }
}
