<?php

namespace App\Models;

use App\Enums\CouponSegment;
use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id', 'itemable_type', 'itemable_id', 'code', 'type', 'value', 'max_discount',
        'segment', 'usage_limit', 'usage_limit_per_user', 'starts_at', 'expires_at', 'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'segment' => CouponSegment::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'username');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user')->withPivot('usage_count')->withTimestamps();
    }

    public function isEligibleFor(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at?->isAfter($now) || $this->expires_at?->isBefore($now)) {
            return false;
        }

        return $this->segment->isEligible($user);
    }

    public function calculateDiscount(int $subtotal): int
    {
        $discount = $this->type === CouponType::Percentage
            ? (int) round($subtotal * $this->value / 100)
            : (int) $this->value;

        return $this->max_discount ? min($discount, (int) $this->max_discount) : $discount;
    }
}
