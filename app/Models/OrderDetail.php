<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A price/quantity snapshot of a single line item at time of purchase.
 * `itemable` currently resolves to `Ticket`, and is designed to extend to
 * future purchasable types (attraction visit, cinema screening, ...).
 */
class OrderDetail extends Model
{
    use HasUlids;

    protected $fillable = [
        'transaction_id', 'itemable_type', 'itemable_id', 'name', 'price', 'quantity',
        'discount', 'subtotal',
    ];

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
