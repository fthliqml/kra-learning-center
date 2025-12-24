<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\SurveyTemplateOption;

class SurveyTemplateSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $templates = [
      [
        'title' => 'Evaluasi Pelatihan Operator Excavator',
        'description' => 'Survei untuk menilai efektivitas pelatihan operator alat berat.',
        'status' => 'active',
        'level' => 1,
        'questions' => [
          [
            'text' => 'Bagaimana tingkat kepuasan Anda terhadap materi pelatihan yang disampaikan?',
            'type' => 'multiple',
            'options' => ['Sangat Puas', 'Puas', 'Cukup', 'Kurang Puas', 'Tidak Puas'],
          ],
          [
            'text' => 'Apakah instruktur menyampaikan materi dengan jelas dan mudah dipahami?',
            'type' => 'multiple',
            'options' => ['Sangat Setuju', 'Setuju', 'Netral', 'Tidak Setuju', 'Sangat Tidak Setuju'],
          ],
          [
            'text' => 'Bagaimana penilaian Anda terhadap fasilitas dan peralatan pelatihan?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
          ],
          [
            'text' => 'Apakah durasi pelatihan sudah sesuai dengan kebutuhan?',
            'type' => 'multiple',
            'options' => ['Sangat Sesuai', 'Sesuai', 'Cukup', 'Kurang Sesuai', 'Tidak Sesuai'],
          ],
          [
            'text' => 'Apa saran Anda untuk meningkatkan kualitas pelatihan ini?',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
      [
        'title' => 'Survei Kepatuhan Safety Pekerja Lapangan',
        'description' => 'Menilai kepatuhan terhadap standar keselamatan di area kerja.',
        'status' => 'active',
        'level' => 1,
        'questions' => [
          [
            'text' => 'Seberapa sering Anda menggunakan APD lengkap saat bekerja?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Bagaimana pemahaman Anda terhadap prosedur keselamatan kerja?',
            'type' => 'multiple',
            'options' => ['Sangat Paham', 'Paham', 'Cukup Paham', 'Kurang Paham', 'Tidak Paham'],
          ],
          [
            'text' => 'Apakah briefing safety pagi memberikan informasi yang bermanfaat?',
            'type' => 'multiple',
            'options' => ['Sangat Bermanfaat', 'Bermanfaat', 'Cukup', 'Kurang Bermanfaat', 'Tidak Bermanfaat'],
          ],
          [
            'text' => 'Bagaimana kondisi peralatan safety di area kerja Anda?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
          ],
          [
            'text' => 'Apa kendala utama dalam menerapkan prosedur keselamatan kerja?',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
      [
        'title' => 'Evaluasi Workshop Maintenance Alat Berat',
        'description' => 'Menilai efektivitas workshop pemeliharaan mesin dan alat berat.',
        'status' => 'active',
        'level' => 1,
        'questions' => [
          [
            'text' => 'Bagaimana tingkat kepuasan Anda terhadap praktik hands-on dalam workshop?',
            'type' => 'multiple',
            'options' => ['Sangat Puas', 'Puas', 'Cukup', 'Kurang Puas', 'Tidak Puas'],
          ],
          [
            'text' => 'Apakah materi workshop relevan dengan pekerjaan sehari-hari Anda?',
            'type' => 'multiple',
            'options' => ['Sangat Relevan', 'Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
          ],
          [
            'text' => 'Bagaimana kualitas alat dan spare part yang digunakan dalam workshop?',
            'type' => 'multiple',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
          ],
          [
            'text' => 'Apakah Anda merasa lebih percaya diri setelah mengikuti workshop ini?',
            'type' => 'multiple',
            'options' => ['Sangat Percaya Diri', 'Percaya Diri', 'Biasa Saja', 'Kurang Percaya Diri', 'Tidak Percaya Diri'],
          ],
          [
            'text' => 'Topik maintenance apa yang perlu ditambahkan di workshop berikutnya?',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
      [
        'title' => 'Feedback Program Kaizen Mingguan',
        'description' => 'Mengukur partisipasi dan efektivitas program continuous improvement.',
        'status' => 'active',
        'level' => 1,
        'questions' => [
          [
            'text' => 'Seberapa aktif Anda berpartisipasi dalam program Kaizen?',
            'type' => 'multiple',
            'options' => ['Sangat Aktif', 'Aktif', 'Cukup Aktif', 'Kurang Aktif', 'Tidak Aktif'],
          ],
          [
            'text' => 'Apakah ide Kaizen Anda mendapat respon yang baik dari atasan?',
            'type' => 'multiple',
            'options' => ['Selalu', 'Sering', 'Kadang-kadang', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Bagaimana dampak program Kaizen terhadap produktivitas tim Anda?',
            'type' => 'multiple',
            'options' => ['Sangat Positif', 'Positif', 'Netral', 'Negatif', 'Sangat Negatif'],
          ],
          [
            'text' => 'Apakah reward untuk ide Kaizen terbaik sudah memadai?',
            'type' => 'multiple',
            'options' => ['Sangat Memadai', 'Memadai', 'Cukup', 'Kurang Memadai', 'Tidak Memadai'],
          ],
          [
            'text' => 'Berikan contoh improvement yang sudah Anda implementasikan melalui Kaizen.',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
      [
        'title' => 'Survei Efektivitas Program 5S Area Produksi',
        'description' => 'Menilai penerapan prinsip 5S (Seiri, Seiton, Seiso, Seiketsu, Shitsuke) di area kerja.',
        'status' => 'active',
        'level' => 1,
        'questions' => [
          [
            'text' => 'Bagaimana tingkat kerapian area kerja Anda saat ini?',
            'type' => 'multiple',
            'options' => ['Sangat Rapi', 'Rapi', 'Cukup Rapi', 'Kurang Rapi', 'Tidak Rapi'],
          ],
          [
            'text' => 'Apakah semua peralatan sudah memiliki tempat penyimpanan yang jelas?',
            'type' => 'multiple',
            'options' => ['Semua', 'Sebagian Besar', 'Sebagian', 'Sedikit', 'Tidak Ada'],
          ],
          [
            'text' => 'Seberapa rutin tim Anda melakukan cleaning area kerja?',
            'type' => 'multiple',
            'options' => ['Setiap Hari', 'Beberapa Kali Seminggu', 'Seminggu Sekali', 'Jarang', 'Tidak Pernah'],
          ],
          [
            'text' => 'Apakah standar 5S sudah dipahami oleh seluruh anggota tim?',
            'type' => 'multiple',
            'options' => ['Sangat Paham', 'Paham', 'Cukup Paham', 'Kurang Paham', 'Tidak Paham'],
          ],
          [
            'text' => 'Apa tantangan terbesar dalam menerapkan 5S di area kerja Anda?',
            'type' => 'essay',
            'options' => [],
          ],
        ],
      ],
    ];

    foreach ($templates as $templateData) {
      $questions = $templateData['questions'];
      unset($templateData['questions']);

      $template = SurveyTemplate::create($templateData);

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
  }
}
