<?php

namespace App\Models;

use App\Enums\SpeakingFormat;
use App\Enums\SpeakingRole;
use Database\Factories\SpeakingEngagementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeakingEngagement extends Model
{
    /** @use HasFactory<SpeakingEngagementFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'organizer', 'role', 'format', 'location', 'starts_at', 'ends_at',
        'registration_url', 'description', 'poster', 'is_published',
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
}
