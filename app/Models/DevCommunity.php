<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\CommunityPlatform;
use App\Traits\Models\HasEnglishContent;
use Database\Factories\DevCommunityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property CommunityPlatform $platform
 * @property string|null $description
 * @property string $invite_url
 * @property int|null $member_count
 * @property bool $is_featured
 */
class DevCommunity extends Model
{
    /** @use HasFactory<DevCommunityFactory> */
    use HasEnglishContent, HasFactory, RecordsActivity;

    /** @var list<string> */
    protected array $englishAttributes = ['description'];

    /** Admin lists carry the raw text of every language, for the edit form. */
    protected bool $withTranslations = true;

    protected $fillable = ['name', 'platform', 'description', 'invite_url', 'member_count', 'is_featured', 'description_en'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => CommunityPlatform::class,
            'member_count' => 'integer',
            'is_featured' => 'boolean',
        ];
    }
}
