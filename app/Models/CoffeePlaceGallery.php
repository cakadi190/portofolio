<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoffeePlaceGallery extends Model
{
    protected $fillable = ['coffee_place_id', 'image_url', 'description'];

    /**
     * @return BelongsTo<CoffeePlace, $this>
     */
    public function coffeePlace(): BelongsTo
    {
        return $this->belongsTo(CoffeePlace::class);
    }
}
