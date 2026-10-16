<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\AwardType;
use App\Traits\Models\HasEnglishContent;
use Database\Factories\AwardFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Award extends Model
{
    /** @use HasFactory<AwardFactory> */
    use HasEnglishContent, HasFactory, RecordsActivity;

    /** @var list<string> */
    protected array $englishAttributes = ['title'];

    /** Admin lists carry the raw text of every language, for the edit form. */
    protected bool $withTranslations = true;

    protected $fillable = ['event_name', 'title', 'type', 'year', 'rank', 'awarded_at', 'title_en'];

    protected $appends = ['icon'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AwardType::class,
            'year' => 'integer',
            'rank' => 'integer',
            'awarded_at' => 'date:Y-m-d',
        ];
    }

    /**
     * Iconify icon id derived from the award: a trophy for podium places, a
     * medal for other ranks, and a certificate for unranked or certification entries.
     *
     * @return Attribute<string, never>
     */
    protected function icon(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->type === AwardType::Certification || $this->rank === null || Str::contains(Str::lower($this->title), ['certif', 'sertif'])) {
                return 'mdi:certificate';
            }

            return $this->rank <= 3 ? 'fa6-solid:trophy' : 'fa6-solid:medal';
        });
    }
}
