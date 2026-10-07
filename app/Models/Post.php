<?php

namespace App\Models;

use App\Traits\Models\AutoGenerateSlug;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use AutoGenerateSlug, HasFactory;

    protected string $slugSource = 'title';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'cover_image', 'is_published', 'published_at',
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
}
