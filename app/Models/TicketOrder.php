<?php

namespace App\Models;

use App\Casts\EncryptionModels\CryptedUnionCast;
use App\Enums\Gender;
use App\Enums\TicketOrderStatus;
use App\Traits\Models\GeneratesDisplayCode;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['nik', 'access_key'])]
class TicketOrder extends Model
{
    use GeneratesDisplayCode, HasUlids;

    protected $fillable = [
        'transaction_id', 'ticket_id', 'first_name', 'last_name', 'email', 'phone', 'nik',
        'gender', 'access_key', 'status', 'checked_in_by', 'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => TicketOrderStatus::class,
            'nik' => CryptedUnionCast::class,
            'access_key' => CryptedUnionCast::class,
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $order): void {
            if ($order->isDirty('verified_at') && $order->verified_at !== null) {
                $order->status = TicketOrderStatus::Attended;
            }
        });
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
