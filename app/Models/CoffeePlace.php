<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\CafePriceTier;
use App\Enums\WifiSpeed;
use App\Traits\Models\HasEnglishContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoffeePlace extends Model
{
    use HasEnglishContent, RecordsActivity;

    /** @var list<string> */
    protected array $englishAttributes = ['description'];

    /** Admin lists carry the raw text of every language, for the edit form. */
    protected bool $withTranslations = true;

    protected $fillable = [
        'name', 'address', 'description', 'latitude', 'longitude', 'map_url', 'image',
        'wifi_provider', 'wifi_speed', 'price_tier', 'park_fee', 'opens_at', 'closes_at',
        'region', 'facilities', 'is_recommended',
        'description_en',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:15',
            'longitude' => 'decimal:15',
            'wifi_speed' => WifiSpeed::class,
            'price_tier' => CafePriceTier::class,
            'park_fee' => 'integer',
            'facilities' => 'array',
            'is_recommended' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CoffeePlaceGallery, $this>
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(CoffeePlaceGallery::class);
    }
}
