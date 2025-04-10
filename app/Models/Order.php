<?php

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
 * @property int $user_id
 * @property string $destination
 * @property Carbon $departure_date
 * @property Carbon $return_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property OrderStatus $status
 * @property-read User $user
 *
 * @method static Builder<Order>|static withStatus(string $status)
 * @method static Builder<Order>|static inDateRange(?string $startDate, ?string $endDate)
 * @method static Builder<Order>|static withDestination(?string $destination)
 */
class Order extends Model
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
     * Get the user that owns the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the order can be canceled.
     */
    public function canBeCanceled(): bool
    {
        return $this->status !== OrderStatus::CANCELED;
    }

    /**
     * Check if the order status can be updated by the given user.
     */
    public function canBeUpdatedBy(User $user): bool
    {
        return $this->user_id !== $user->id;
    }

    /**
     * Scope a query to only include orders with a specific status.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    #[Scope]
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter orders by date range.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    #[Scope]
    public function scopeInDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('departure_date', [$startDate, $endDate])
                ->orWhereBetween('return_date', [$startDate, $endDate]);
        }

        return $query;
    }

    /**
     * Scope a query to filter orders by destination.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    #[Scope]
    public function scopeWithDestination(Builder $query, ?string $destination): Builder
    {
        if ($destination) {
            return $query->where('destination', 'like', "%{$destination}%");
        }

        return $query;
    }
}
