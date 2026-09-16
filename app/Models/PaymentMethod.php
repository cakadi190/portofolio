<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasUlids;

    protected $fillable = [
        'gateway', 'name', 'code', 'logo', 'admin_fee_flat', 'admin_fee_percentage',
        'admin_fee_min', 'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'admin_fee_flat' => 'decimal:2',
            'admin_fee_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PaymentChannel, $this>
     */
    public function channels(): HasMany
    {
        return $this->hasMany(PaymentChannel::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function calculateAdminFee(int $amount): int
    {
        $percentageFee = (int) round($amount * (float) $this->admin_fee_percentage / 100);

        return max((int) $this->admin_fee_flat + $percentageFee, (int) $this->admin_fee_min);
    }

    public function calculateCheckoutTotal(int $amount): int
    {
        return $amount + $this->calculateAdminFee($amount);
    }
}
