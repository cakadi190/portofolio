<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use RecordsActivity;

    protected $fillable = ['name', 'description', 'start_date', 'end_date'];

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
}
