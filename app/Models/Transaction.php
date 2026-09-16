<?php

namespace App\Models;

use App\Casts\EncryptionModels\CryptedUnionCast;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Hidden(['billing_nik'])]
class Transaction extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id', 'coupon_id', 'payment_method_id', 'payment_channel_id', 'gateway',
        'gateway_reference', 'gross_amount', 'provider_fee', 'admin_fee', 'net_amount', 'status',
        'billing_name', 'billing_email', 'billing_phone', 'billing_nik', 'paid_at', 'expired_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'billing_nik' => CryptedUnionCast::class,
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return BelongsTo<PaymentChannel, $this>
     */
    public function paymentChannel(): BelongsTo
    {
        return $this->belongsTo(PaymentChannel::class);
    }

    /**
     * @return HasMany<OrderDetail, $this>
     */
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    /**
     * @return HasMany<TicketOrder, $this>
     */
    public function ticketOrders(): HasMany
    {
        return $this->hasMany(TicketOrder::class);
    }

    public function calculateNetAmount(): int
    {
        return $this->gross_amount - $this->provider_fee - $this->admin_fee;
    }
}
