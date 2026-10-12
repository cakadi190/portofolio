<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoffeePlaceGallery extends Model
{
    use RecordsActivity;

    protected $fillable = ['coffee_place_id', 'image_url', 'description'];

    /**
     * @return BelongsTo<CoffeePlace, $this>
     */
    public function coffeePlace(): BelongsTo
    {
        return $this->belongsTo(CoffeePlace::class);
    }
}
