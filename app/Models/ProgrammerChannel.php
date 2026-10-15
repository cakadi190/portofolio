<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\ChannelPlatform;
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
    use HasFactory, RecordsActivity;

    protected $fillable = ['name', 'platform', 'description', 'url', 'is_featured'];

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
