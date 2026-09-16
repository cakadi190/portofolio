<?php

namespace App\Models;

use App\Traits\Models\AutoGenerateSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    use AutoGenerateSlug;

    protected $fillable = ['name', 'slug'];

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
