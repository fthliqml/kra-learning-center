<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\SurveyTemplateOption;
use App\Models\SurveyTemplateDefault;

class SurveyTemplateSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $templates = [
      // Level 1 templates: Kepuasan terhadap instruktur/trainer
      [
        'title' => 'Survey Kepuasan Instruktur - Umum',
        'description' => 'Survey untuk menilai kepuasan peserta terhadap instruktur atau trainer pelatihan.',
        'status' => 'active',
        'level' => 1,
        'questions' => [
          [
            'text' => 'Bagaimana penilaian Anda terhadap kemampuan instruktur dalam mengajar?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Buruk'],
          ],
          [
            'text' => 'Apakah instruktur menjawab pertanyaan peserta dengan jelas?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Bagaimana sikap instruktur selama pelatihan?',
            'type' => 'multiple',
            'options' => ['Sangat Ramah', 'Ramah', 'Cukup', 'Kurang Ramah', 'Tidak Ramah'],
          ],
          [
            'text' => 'Apakah materi yang disampaikan instruktur mudah dipahami?',
            'type' => 'multiple',
            'options' => ['Sangat Mudah', 'Mudah', 'Cukup', 'Sulit', 'Sangat Sulit'],
          ],
          [
            'text' => 'Saran untuk instruktur agar pelatihan lebih baik:',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
      [
        'title' => 'Survey Kepuasan Instruktur - Teknis',
        'description' => 'Survey kepuasan peserta terhadap instruktur pelatihan teknis.',
        'status' => 'active',
        'level' => 1,
        'questions' => [
          [
            'text' => 'Bagaimana penjelasan instruktur mengenai materi teknis?',
            'type' => 'multiple',
            'options' => ['Sangat Jelas', 'Jelas', 'Cukup', 'Kurang Jelas', 'Tidak Jelas'],
          ],
          [
            'text' => 'Apakah instruktur memberikan contoh praktik yang relevan?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Bagaimana kemampuan instruktur dalam mengatasi masalah teknis saat pelatihan?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Buruk'],
          ],
          [
            'text' => 'Apakah instruktur mendorong peserta untuk aktif bertanya?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Saran untuk instruktur pelatihan teknis:',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
      // Level 3 templates: Kepuasan supervisor terhadap karyawan
      [
        'title' => 'Survey Supervisor - Kinerja Karyawan',
        'description' => 'Survey supervisor untuk menilai kinerja karyawan setelah pelatihan.',
        'status' => 'active',
        'level' => 3,
        'questions' => [
          [
            'text' => 'Bagaimana peningkatan kinerja karyawan setelah mengikuti pelatihan?',
            'type' => 'multiple',
            'options' => ['Sangat Meningkat', 'Meningkat', 'Cukup', 'Tidak Meningkat', 'Menurun'],
          ],
          [
            'text' => 'Apakah karyawan menerapkan pengetahuan yang didapat dalam pekerjaan?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Bagaimana sikap karyawan terhadap tugas dan tanggung jawab?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Buruk'],
          ],
          [
            'text' => 'Apakah karyawan menunjukkan inisiatif dalam pekerjaan?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Saran untuk pengembangan karyawan:',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
      [
        'title' => 'Survey Supervisor - Perilaku Kerja',
        'description' => 'Survey supervisor untuk menilai perilaku kerja karyawan pasca pelatihan.',
        'status' => 'active',
        'level' => 3,
        'questions' => [
          [
            'text' => 'Bagaimana disiplin karyawan dalam menjalankan tugas?',
            'type' => 'multiple',
            'options' => ['Sangat Disiplin', 'Disiplin', 'Cukup', 'Kurang Disiplin', 'Tidak Disiplin'],
          ],
          [
            'text' => 'Apakah karyawan bekerja sama dengan tim secara efektif?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Buruk'],
          ],
          [
            'text' => 'Bagaimana komunikasi karyawan dengan atasan dan rekan kerja?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Buruk'],
          ],
          [
            'text' => 'Apakah karyawan mematuhi prosedur dan aturan kerja?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Saran untuk peningkatan perilaku kerja karyawan:',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
    ];

    $levelDefaults = [];
    foreach ($templates as $templateData) {
      $questions = $templateData['questions'];
      unset($templateData['questions']);

      $template = SurveyTemplate::create($templateData);

      // Simpan id template pertama untuk tiap level sebagai default
      $level = $template->level;
      if (!isset($levelDefaults[$level])) {
        $levelDefaults[$level] = $template->id;
      }

      foreach ($questions as $order => $questionData) {
        $question = SurveyTemplateQuestion::create([
          'survey_template_id' => $template->id,
          'text' => $questionData['text'],
          'question_type' => $questionData['type'],
          'order' => $order + 1,
        ]);

        if ($questionData['type'] === 'multiple') {
          foreach ($questionData['options'] as $optOrder => $optText) {
            SurveyTemplateOption::create([
              'survey_template_question_id' => $question->id,
              'text' => $optText,
              'order' => $optOrder + 1,
            ]);
          }
        }
      }
    }

    // Set default template untuk tiap level
    foreach ($levelDefaults as $level => $templateId) {
      SurveyTemplateDefault::setDefaultForLevel($templateId, $level);
    }
  }
}
