<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SurveyTemplate;

class SurveyTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'title' => 'Survey Template #1',
                'description' => 'Template untuk evaluasi kinerja bulanan.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survey Template #2',
                'description' => 'Template untuk survei kepuasan pelanggan.',
                'status' => 'active',
                'level' => 2,
            ],
            [
                'title' => 'Survey Template #3',
                'description' => 'Template survei onboarding karyawan baru.',
                'status' => 'active',
                'level' => 1,
            ],
            [
                'title' => 'Survey Template #4',
                'description' => 'Template untuk umpan balik pelatihan internal.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Survey Template #5',
                'description' => 'Template evaluasi rekan kerja antar divisi.',
                'status' => 'active',
                'level' => 2,
            ],
            [
                'title' => 'Survey Template #6',
                'description' => 'Template survei kepuasan pengguna aplikasi.',
                'status' => 'active',
                'level' => 3,
            ],
            [
                'title' => 'Survey Template #7',
                'description' => 'Template survei harian untuk laporan tim.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survey Template #8',
                'description' => 'Template evaluasi kegiatan mingguan.',
                'status' => 'active',
                'level' => 2,
            ],
            [
                'title' => 'Survey Template #9',
                'description' => 'Template survei untuk project review.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Survey Template #10',
                'description' => 'Template survei akhir tahun.',
                'status' => 'active',
                'level' => 1,
            ],
        ];

        SurveyTemplate::insert($templates);
    }
}
