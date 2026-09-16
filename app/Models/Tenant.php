<?php

namespace App\Models;

use App\Casts\EncryptionModels\CryptedDateCast;
use App\Casts\EncryptionModels\CryptedUnionCast;
use App\Enums\TenantEntityType;
use App\Enums\TenantStatus;
use App\Traits\Models\AutoGenerateSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use AutoGenerateSlug, SoftDeletes;

    protected $primaryKey = 'username';

    protected $keyType = 'string';

    public $incrementing = false;

    protected string $slugSource = 'name';

    protected string $slugColumn = 'username';

    protected $fillable = [
        'username', 'name', 'entity_type', 'status', 'logo', 'description',
        'email', 'phone', 'address', 'city',
        'pic_name', 'pic_nik', 'pic_date_of_birth', 'pic_phone',
        'rejection_reason', 'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => TenantEntityType::class,
            'status' => TenantStatus::class,
            'pic_nik' => CryptedUnionCast::class,
            'pic_date_of_birth' => CryptedDateCast::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<TenantDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(TenantDocument::class, 'tenant_id', 'username');
    }

    /**
     * @return HasMany<TenantInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class, 'tenant_id', 'username');
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'tenant_id', 'username');
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'tenant_id', 'username');
    }

    /**
     * @return HasMany<BankAccount, $this>
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'tenant_id', 'username');
    }

    /**
     * @return HasMany<Withdrawal, $this>
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'tenant_id', 'username');
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'tenant_id', 'username');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships', 'tenant_id', 'user_id', 'username')
            ->withPivot(['role_id', 'invited_by', 'joined_at']);
    }
}
