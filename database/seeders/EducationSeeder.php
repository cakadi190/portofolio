<?php

namespace Database\Seeders;

use App\Models\Education;
use Illuminate\Database\Seeder;

class EducationSeeder extends Seeder
{
    /**
     * Seed the education history.
     */
    public function run(): void
    {
        $educations = [
            [
                'name' => 'Politeknik Negeri Madiun',
                'logo' => '/images/education/pnm.png',
                'website' => 'https://www.pnm.ac.id',
                'level' => 'university',
                'grade' => 'Diploma 3 (D3)',
                'department' => 'Teknik',
                'study_program' => 'Teknologi Informasi',
                'start_date' => '2022-07-12',
                'end_date' => '2026-02-15',
                'place' => 'Jl. Serayu, Taman, Kota Madiun, Jawa Timur',
                'academic_score_type' => 'gpa',
                'academic_score_label' => 'IPK',
                'academic_score_value' => 3.34,
                'academic_score_scale' => 4.00,
            ],
            [
                'name' => 'SMA Negeri 1 Ngawi',
                'logo' => '/images/education/smasa.png',
                'website' => 'https://www.sman1ngawi.sch.id',
                'level' => 'shs',
                'grade' => null,
                'department' => 'Matematika dan IPA',
                'study_program' => null,
                'start_date' => '2018-07-12',
                'end_date' => '2021-03-17',
                'place' => 'Jl. Ahmad Yani No.45, Wareng, Beran, Kec. Ngawi, Kabupaten Ngawi, Jawa Timur 63216',
                'academic_score_type' => 'school_exam',
                'academic_score_label' => 'Nilai Ujian Sekolah',
                'academic_score_value' => 80.25,
                'academic_score_scale' => 100,
            ],
            [
                'name' => 'SMP Negeri 1 Padas',
                'logo' => null,
                'website' => null,
                'level' => 'jhs',
                'grade' => null,
                'department' => null,
                'study_program' => null,
                'start_date' => '2015-07-12',
                'end_date' => '2018-03-17',
                'place' => 'Jl. Raya Padas-Ngawi, Padas I, Padas, Kec. Padas, Kabupaten Ngawi, Jawa Timur 63281',
                'academic_score_type' => null,
                'academic_score_label' => null,
                'academic_score_value' => null,
                'academic_score_scale' => null,
            ],
            [
                'name' => 'SD Negeri Munggut 1',
                'logo' => null,
                'website' => null,
                'level' => 'es',
                'grade' => null,
                'department' => null,
                'study_program' => null,
                'start_date' => '2009-07-12',
                'end_date' => '2015-06-17',
                'place' => 'Jl. A. Yani No.35, Munggut, Kec. Padas, Kabupaten Ngawi, Jawa Timur 63281',
                'academic_score_type' => null,
                'academic_score_label' => null,
                'academic_score_value' => null,
                'academic_score_scale' => null,
            ],
            [
                'name' => 'TPQ Al-Falahiyyah',
                'logo' => null,
                'website' => null,
                'level' => 'kg',
                'grade' => null,
                'department' => null,
                'study_program' => null,
                'start_date' => '2007-05-21',
                'end_date' => '2009-03-17',
                'place' => 'Tangerang, Banten',
                'academic_score_type' => null,
                'academic_score_label' => null,
                'academic_score_value' => null,
                'academic_score_scale' => null,
            ],
        ];

        foreach ($educations as $education) {
            Education::query()->create($education);
        }
    }
}
