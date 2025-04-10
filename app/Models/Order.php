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
