<?php

namespace App\Models;

use App\Enums\BlogCommentStatus;
use Database\Factories\BlogCommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reader comment on a blog post, nestable to any depth through `parent_id`.
 *
 * Only `body` is mass assignable. `post_id`, `user_id`, `parent_id` and
 * `status` are always set explicitly by trusted server code.
 *
 * @property int $id
 * @property int $post_id
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property string $body
 * @property BlogCommentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BlogComment extends Model
{
    /** @use HasFactory<BlogCommentFactory> */
    use HasFactory;

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
     * Deleting a comment lifts its replies one level up so the rest of the
     * thread survives instead of being deleted or orphaned to the root.
     */
    protected static function booted(): void
    {
        static::deleting(function (BlogComment $comment): void {
            $comment->replies()->update(['parent_id' => $comment->parent_id]);
        });
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BlogComment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<BlogComment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @param  Builder<BlogComment>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', BlogCommentStatus::Approved);
    }
}
