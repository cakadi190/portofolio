<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\BlogCommentStatus;
use Database\Factories\GuestbookEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A guestbook message, nestable to any depth through `parent_id`.
 *
 * Only `body` is mass assignable. `user_id`, `guest_name`, `guest_email`, `parent_id` and `status` are
 * always set explicitly by trusted server code.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $guest_name
 * @property string|null $guest_email
 * @property int|null $parent_id
 * @property string $body
 * @property BlogCommentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GuestbookEntry extends Model
{
    /** @use HasFactory<GuestbookEntryFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = ['body'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BlogCommentStatus::class,
        ];
    }

    /**
     * Deleting an entry lifts its replies one level up so the thread survives.
     */
    protected static function booted(): void
    {
        static::deleting(function (GuestbookEntry $entry): void {
            $entry->replies()->update(['parent_id' => $entry->parent_id]);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<GuestbookEntry, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<GuestbookEntry, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @param  Builder<GuestbookEntry>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', BlogCommentStatus::Approved);
    }
}
