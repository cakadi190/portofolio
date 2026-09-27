<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\EncryptionModels\CryptedDateCast;
use App\Casts\EncryptionModels\CryptedUnionCast;
use App\Enums\Gender;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $account_type
 * @property string|null $phone
 * @property Gender|null $gender
 * @property bool $is_student
 * @property string|null $nik
 * @property Carbon|null $date_of_birth
 * @property string|null $address
 * @property string|null $avatar
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'account_type', 'phone', 'gender', 'is_student', 'nik', 'date_of_birth', 'address', 'avatar'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'nik', 'date_of_birth'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'account_type' => UserRole::class,
            'gender' => Gender::class,
            'is_student' => 'boolean',
            'nik' => CryptedUnionCast::class,
            'date_of_birth' => CryptedDateCast::class,
        ];
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'memberships', 'user_id', 'tenant_id')
            ->withPivot(['role_id', 'invited_by', 'joined_at']);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Resolve the caller's membership for a given tenant, or the platform-level
     * membership when `$tenant` is null.
     */
    public function membershipFor(?Tenant $tenant = null): ?Membership
    {
        return $this->memberships()
            ->where('tenant_id', $tenant?->username)
            ->first();
    }

    public static function whereEmail(string $email): User
    {
        return self::where('email', "$email")->first();
    }

    public function roleFor(?Tenant $tenant = null): ?Role
    {
        return $this->membershipFor($tenant)?->role;
    }

    public function hasPermission(string $permission, ?Tenant $tenant = null): bool
    {
        return $this->roleFor($tenant)?->permissions->contains('slug', $permission) ?? false;
    }
}
