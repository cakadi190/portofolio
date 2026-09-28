<?php

namespace App\Models;

use App\Enums\CafePriceTier;
use App\Enums\WifiSpeed;
use Illuminate\Database\Eloquent\Model;

class CoffeePlace extends Model
{
    protected $fillable = [
        'name', 'address', 'description', 'latitude', 'longitude', 'map_url', 'image',
        'wifi_provider', 'wifi_speed', 'price_tier', 'park_fee', 'opens_at', 'closes_at',
        'region', 'is_recommended',
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
            'is_recommended' => 'boolean',
        ];
    }
}
