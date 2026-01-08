<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use Illuminate\Database\Seeder;

class CourseTestSeeder extends Seeder
{
  /**
   * Run the database seeds.
   * Creates pretest and posttest for each Course (LMS).
   */
  public function run(): void
  {
    $courses = Course::all();

    foreach ($courses as $course) {
      $this->createTestsForCourse($course);
    }
  }

  /**
   * Create pretest (5 MC) and posttest (4 MC + 1 essay) for a course.
   */
  private function createTestsForCourse(Course $course): void
  {
    $courseTitle = $course->title;

    // Multiple choice questions for pretest (5 MC × 20 points = 100 points)
    $pretestQuestions = [
      [
        'text' => "Apa tujuan utama Anda mengikuti course \"{$courseTitle}\"?",
        'options' => [
          ['text' => 'Meningkatkan kompetensi dan efisiensi kerja', 'is_correct' => true],
          ['text' => 'Hanya untuk memenuhi kewajiban training', 'is_correct' => false],
          ['text' => 'Mengisi waktu luang di kantor', 'is_correct' => false],
          ['text' => 'Mendapatkan sertifikat tanpa belajar', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Seberapa penting materi \"{$courseTitle}\" menurut Anda dalam mendukung operasional harian?",
        'options' => [
          ['text' => 'Sangat penting untuk standar kualitas kerja', 'is_correct' => true],
          ['text' => 'Kurang penting, bisa dipelajari sambil jalan', 'is_correct' => false],
          ['text' => 'Tidak relevan dengan tugas saya', 'is_correct' => false],
          ['text' => 'Hanya penting bagi manajemen saja', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Apa yang Anda ketahui tentang implementasi awal \"{$courseTitle}\"?",
        'options' => [
          ['text' => 'Perlu pemahaman teori dasar sebelum praktik', 'is_correct' => true],
          ['text' => 'Bisa langsung dilakukan tanpa instruksi', 'is_correct' => false],
          ['text' => 'Tidak memerlukan persiapan khusus', 'is_correct' => false],
          ['text' => 'Hanya butuh peralatan baru saja', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Mengapa dokumentasi progres sangat diperlukan dalam materi \"{$courseTitle}\"?",
        'options' => [
          ['text' => 'Sebagai bahan evaluasi dan perbaikan berkelanjutan', 'is_correct' => true],
          ['text' => 'Hanya untuk menambah pekerjaan administrasi', 'is_correct' => false],
          ['text' => 'Tidak ada manfaatnya bagi tim', 'is_correct' => false],
          ['text' => 'Agar terlihat sibuk di depan atasan', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Siapa yang paling diuntungkan jika standar dalam \"{$courseTitle}\" dijalankan dengan benar?",
        'options' => [
          ['text' => 'Seluruh organisasi dan pelanggan', 'is_correct' => true],
          ['text' => 'Hanya departemen training', 'is_correct' => false],
          ['text' => 'Hanya direktur perusahaan', 'is_correct' => false],
          ['text' => 'Hanya kompetitor perusahaan', 'is_correct' => false],
        ],
      ],
    ];

    // Multiple choice questions for posttest (4 MC × 20 points = 80 points)
    $mcQuestions = [
      [
        'text' => "Setelah mempelajari \"{$courseTitle}\", apa langkah pertama yang harus dilakukan untuk mengimplementasikan pengetahuan ini?",
        'options' => [
          ['text' => 'Melakukan assessment kondisi saat ini dan membuat rencana aksi', 'is_correct' => true],
          ['text' => 'Langsung implementasi tanpa perencanaan', 'is_correct' => false],
          ['text' => 'Menunggu instruksi dari atasan', 'is_correct' => false],
          ['text' => 'Menyimpan materi untuk dibaca nanti', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Apa manfaat utama dari penerapan konsep \"{$courseTitle}\" di tempat kerja?",
        'options' => [
          ['text' => 'Meningkatkan produktivitas dan kualitas kerja', 'is_correct' => true],
          ['text' => 'Menambah beban kerja karyawan', 'is_correct' => false],
          ['text' => 'Memperlambat proses operasional', 'is_correct' => false],
          ['text' => 'Tidak ada dampak signifikan', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Bagaimana cara terbaik untuk mempertahankan hasil pembelajaran dari \"{$courseTitle}\"?",
        'options' => [
          ['text' => 'Dengan monitoring berkala dan evaluasi rutin', 'is_correct' => true],
          ['text' => 'Tidak perlu dipertahankan', 'is_correct' => false],
          ['text' => 'Hanya saat ada audit atau inspeksi', 'is_correct' => false],
          ['text' => 'Membuat laporan tahunan saja', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Siapa yang bertanggung jawab dalam keberhasilan implementasi \"{$courseTitle}\"?",
        'options' => [
          ['text' => 'Seluruh tim yang terlibat secara kolaboratif', 'is_correct' => true],
          ['text' => 'Hanya supervisor atau atasan langsung', 'is_correct' => false],
          ['text' => 'Hanya departemen training', 'is_correct' => false],
          ['text' => 'Hanya peserta training secara individu', 'is_correct' => false],
        ],
      ],
    ];

    // Essay question for posttest (1 essay × 20 points = 20 points)
    $posttestEssay = [
      'text' => "Berdasarkan seluruh materi yang telah Anda pelajari di \"{$courseTitle}\", jelaskan secara komprehensif bagaimana Anda akan mengintegrasikan pengetahuan ini ke dalam rutinitas kerja harian Anda. Sertakan langkah-langkah konkret dan timeline yang realistis.",
    ];

    // Create Pretest (5 MC questions)
    $pretest = Test::updateOrCreate(
      [
        'course_id' => $course->id,
        'type' => 'pretest',
      ],
      [
        'passing_score' => 70,
        'max_attempts' => 1,
        'randomize_question' => true,
        'show_result_immediately' => true,
      ]
    );

    // Clear existing questions
    $pretest->questions()->delete();

    // Create pretest MC questions (5 × 20 points = 100)
    foreach ($pretestQuestions as $order => $questionData) {
      $question = TestQuestion::create([
        'test_id' => $pretest->id,
        'question_type' => 'multiple',
        'text' => $questionData['text'],
        'order' => $order,
        'max_points' => 20,
      ]);

      foreach ($questionData['options'] as $optOrder => $optionData) {
        TestQuestionOption::create([
          'question_id' => $question->id,
          'text' => $optionData['text'],
          'is_correct' => $optionData['is_correct'],
          'order' => $optOrder,
        ]);
      }
    }

    // Create Posttest (4 MC + 1 Essay)
    $posttest = Test::updateOrCreate(
      [
        'course_id' => $course->id,
        'type' => 'posttest',
      ],
      [
        'passing_score' => 75,
        'max_attempts' => 5,
        'randomize_question' => true,
        'show_result_immediately' => true,
      ]
    );

    // Clear existing questions
    $posttest->questions()->delete();

    // Create posttest MC questions (4 × 20 points = 80)
    foreach ($mcQuestions as $order => $questionData) {
      $question = TestQuestion::create([
        'test_id' => $posttest->id,
        'question_type' => 'multiple',
        'text' => $questionData['text'],
        'order' => $order,
        'max_points' => 20,
      ]);

      foreach ($questionData['options'] as $optOrder => $optionData) {
        TestQuestionOption::create([
          'question_id' => $question->id,
          'text' => $optionData['text'],
          'is_correct' => $optionData['is_correct'],
          'order' => $optOrder,
        ]);
      }
    }

    // Create posttest essay question (1 × 20 points = 20)
    TestQuestion::create([
      'test_id' => $posttest->id,
      'question_type' => 'essay',
      'text' => $posttestEssay['text'],
      'order' => count($mcQuestions), // After MC questions
      'max_points' => 20,
    ]);
  }
}
