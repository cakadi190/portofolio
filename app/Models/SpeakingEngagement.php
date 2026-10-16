<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\SpeakingFormat;
use App\Enums\SpeakingRole;
use App\Traits\Models\HasEnglishContent;
use Database\Factories\SpeakingEngagementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeakingEngagement extends Model
{
    /** @use HasFactory<SpeakingEngagementFactory> */
    use HasEnglishContent, HasFactory, RecordsActivity;

    /** @var list<string> */
    protected array $englishAttributes = ['title', 'description'];

    /** Admin lists carry the raw text of every language, for the edit form. */
    protected bool $withTranslations = true;

    protected $fillable = [
        'title', 'organizer', 'role', 'format', 'location', 'starts_at', 'ends_at',
        'registration_url', 'description', 'poster', 'is_published',
        'title_en', 'description_en',
    ];

    /**
     * Dates keep the wall-clock time as entered (no timezone conversion) so an
     * event announced as 19.30 WIB is shown as 19.30 everywhere.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => SpeakingRole::class,
            'format' => SpeakingFormat::class,
            'starts_at' => 'datetime:Y-m-d H:i',
            'ends_at' => 'datetime:Y-m-d H:i',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<SpeakingEngagement>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * An engagement is over once its end time passes. Without an end time it
     * ends at its start time, or at the end of that day when no time was set.
     */
    public function hasEnded(): bool
    {
        $now = now('Asia/Jakarta')->format('Y-m-d H:i:s');

        if ($this->ends_at !== null) {
            return $this->ends_at->format('Y-m-d H:i:s') < $now;
        }

        $hasTime = $this->starts_at->format('H:i') !== '00:00';
        $end = $hasTime
            ? $this->starts_at->format('Y-m-d H:i:s')
            : $this->starts_at->format('Y-m-d').' 23:59:59';

        return $end < $now;
    }
}
