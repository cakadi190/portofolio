<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id', 'name', 'stock', 'max_buy', 'max_buy_date', 'price', 'sale_price',
        'is_free', 'is_active', 'need_fill_identity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_buy_date' => 'datetime',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'need_fill_identity' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    /**
     * @return HasMany<TicketOrder, $this>
     */
    public function ticketOrders(): HasMany
    {
        return $this->hasMany(TicketOrder::class);
    }

    /**
     * @return MorphMany<Coupon, $this>
     */
    public function coupons(): MorphMany
    {
        return $this->morphMany(Coupon::class, 'itemable');
    }

    /**
     * @return MorphMany<OrderDetail, $this>
     */
    public function orderDetails(): MorphMany
    {
        return $this->morphMany(OrderDetail::class, 'itemable');
    }

    public function effectivePrice(): int
    {
        return $this->is_free ? 0 : (int) ($this->sale_price ?? $this->price);
    }
}
