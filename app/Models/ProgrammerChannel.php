<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\ChannelPlatform;
use App\Traits\Models\HasEnglishContent;
use Database\Factories\ProgrammerChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property ChannelPlatform $platform
 * @property string|null $description
 * @property string $url
 * @property bool $is_featured
 */
class ProgrammerChannel extends Model
{
    /** @use HasFactory<ProgrammerChannelFactory> */
    use HasEnglishContent, HasFactory, RecordsActivity;

    /** @var list<string> */
    protected array $englishAttributes = ['description'];

    /** Admin lists carry the raw text of every language, for the edit form. */
    protected bool $withTranslations = true;

    protected $fillable = ['name', 'platform', 'description', 'url', 'is_featured', 'description_en'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => ChannelPlatform::class,
            'is_featured' => 'boolean',
        ];
    }
}
