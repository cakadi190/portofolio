<?php

namespace App\Models;

use App\Enums\AcademicScoreType;
use App\Enums\EducationLevel;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    /** Eloquent's pluralizer treats "education" as uncountable and would otherwise guess "education". */
    protected $table = 'educations';

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
            'level' => EducationLevel::class,
            'academic_score_type' => AcademicScoreType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'academic_score_value' => 'decimal:2',
            'academic_score_scale' => 'decimal:2',
        ];
    }
}
