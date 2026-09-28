<?php

namespace App\Models;

use Database\Factories\AwardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    /** @use HasFactory<AwardFactory> */
    use HasFactory;

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
