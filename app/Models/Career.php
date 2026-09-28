<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Career extends Model
{
    protected $fillable = ['position', 'company', 'location', 'start_date', 'end_date'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsToMany<Portfolio, $this>
     */
    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class);
    }
}
