<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = ['event_name', 'title', 'icon', 'year', 'rank', 'awarded_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'rank' => 'integer',
            'awarded_at' => 'date',
        ];
    }
}
