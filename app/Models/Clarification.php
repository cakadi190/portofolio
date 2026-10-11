<?php

namespace App\Models;

use App\Enums\ClarificationVerdict;
use App\Traits\Models\AutoGenerateSlug;
use Database\Factories\ClarificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clarification extends Model
{
    /** @use HasFactory<ClarificationFactory> */
    use AutoGenerateSlug, HasFactory;

    protected string $slugSource = 'title';

    protected $fillable = [
        'title', 'slug', 'claim', 'claim_origin', 'verdict', 'summary', 'content',
        'apology', 'cover_image', 'sources', 'updates', 'withdrawn_at', 'withdrawal_note', 'is_published', 'published_at',
    ];

    /**
     * `sources` is a list of {title, url} evidence links and `updates` a list
     * of {date, note} corrections, both edited together with the article.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verdict' => ClarificationVerdict::class,
            'sources' => 'array',
            'updates' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /**
     * Clarifications listed publicly: published, already due, and not withdrawn.
     *
     * @param  Builder<Clarification>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->where('published_at', '<=', now())->whereNull('withdrawn_at');
    }
}
