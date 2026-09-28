<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\Organization;
use App\Services\ImageService;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    /**
     * Show education and organization history.
     */
    public function index(): Response
    {
        return Inertia::render('education/index', [
            'educations' => Education::query()
                ->orderByDesc('start_date')
                ->get()
                ->map(fn (Education $education): array => [
                    'name' => $education->name,
                    'logo' => ImageService::url($education->logo),
                    'website' => $education->website,
                    'level' => $education->level,
                    'grade' => $education->grade,
                    'department' => $education->department,
                    'studyProgram' => $education->study_program,
                    'startDate' => $education->start_date,
                    'endDate' => $education->end_date,
                    'place' => $education->place,
                    'academicScore' => $education->academic_score_label ? [
                        'label' => $education->academic_score_label,
                        'value' => $education->academic_score_value,
                        'scale' => $education->academic_score_scale,
                    ] : null,
                ]),
            'organizations' => Organization::query()
                ->orderByDesc('start_date')
                ->get()
                ->map(fn (Organization $organization): array => [
                    'name' => $organization->name,
                    'description' => $organization->description,
                    'startDate' => $organization->start_date,
                    'endDate' => $organization->end_date,
                ]),
        ]);
    }
}
