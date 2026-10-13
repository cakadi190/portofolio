<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Traits\Models\AutoGenerateSlug;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A short note: shorter than a blog post, tagged, with no comments.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property bool $is_published
 * @property Carbon|null $published_at
 */
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use AutoGenerateSlug, HasFactory, RecordsActivity;

    protected string $slugSource = 'title';

    protected $fillable = ['user_id', 'title', 'slug', 'excerpt', 'content', 'is_published', 'published_at'];

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
     * @param  Builder<Note>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Estimated reading time in whole minutes, at least one.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->content)) / 200));
    }
}
