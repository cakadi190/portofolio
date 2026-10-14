<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Traits\Models\AutoGenerateSlug;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use AutoGenerateSlug, HasFactory, RecordsActivity, SoftDeletes;

    protected string $slugSource = 'title';

    protected $fillable = [
        'user_id', 'title', 'slug', 'excerpt', 'content', 'cover_image', 'is_published', 'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * The writer of the post. Null once that account has been deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsToMany<PostCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PostCategory::class, 'post_category_post');
    }

    /**
     * @return HasMany<BlogComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    /**
     * Token for the staff-only draft preview URL. It is bound to the slug and
     * the last save, so every edit yields a fresh link and stale ones stop working.
     */
    public function previewHash(): string
    {
        return substr(hash_hmac('sha256', $this->slug.'|'.$this->updated_at?->getTimestamp(), (string) config('app.key')), 0, 16);
    }

    public function previewUrl(): string
    {
        return route('blog.show', [$this, 'action' => 'preview', 'cache' => $this->previewHash()]);
    }
}
