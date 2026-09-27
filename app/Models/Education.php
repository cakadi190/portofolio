<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $fillable = [
        'name', 'logo', 'website', 'level', 'grade', 'department', 'study_program',
        'start_date', 'end_date', 'place', 'academic_score_type', 'academic_score_label',
        'academic_score_value', 'academic_score_scale',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'academic_score_value' => 'decimal:2',
            'academic_score_scale' => 'decimal:2',
        ];
    }
}
