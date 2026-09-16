<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Cart extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'session_id', 'itemable_type', 'itemable_id', 'quantity'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<Cart>  $query
     * @return Builder<Cart>
     */
    public function scopeForOwner(Builder $query, ?User $user, ?string $sessionId): Builder
    {
        return $user
            ? $query->where('user_id', $user->id)
            : $query->whereNull('user_id')->where('session_id', $sessionId);
    }
}
