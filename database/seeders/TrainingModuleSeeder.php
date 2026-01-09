<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use App\Models\TrainingModule;
use Illuminate\Database\Seeder;

class TrainingModuleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get competencies for seeding
    $competencies = Competency::all()->keyBy('code');

    $modules = [
      [
        'title' => '5S Management',
        'competency_code' => 'BMC001',
        'objective' => 'Understanding and implementation of 5S workplace organization',
        'training_content' => '5S principles and practices',
        'method' => 'Classroom & Practice',
        'duration' => 8,
        'frequency' => 2,
      ],
      [
        'title' => '7 HABITS',
        'competency_code' => 'BC001',
        'objective' => 'Develop effective personal and interpersonal habits',
        'training_content' => 'Stephen Covey 7 Habits framework',
        'method' => 'Workshop',
        'duration' => 8,
        'frequency' => 2,
      ],
      [
        'title' => '8 STEPS IMPROVEMENT',
        'competency_code' => 'BMC002',
        'objective' => 'Learn systematic problem solving approach',
        'training_content' => '8 steps of continuous improvement',
        'method' => 'Classroom & Case Study',
        'duration' => 12,
        'frequency' => 3,
      ],
      [
        'title' => 'ADVANCE ASSEMBLY OF AXLE ASSY',
        'competency_code' => 'MMP001',
        'objective' => 'Master advanced assembly techniques for axle assembly',
        'training_content' => 'Axle assembly procedures and quality standards',
        'method' => 'Hands-on Training',
        'duration' => 16,
        'frequency' => 4,
      ],
      [
        'title' => 'ADVANCE ASSEMBLY OF COUNTER SHAFT TRANSMISSION',
        'competency_code' => 'MMP001',
        'objective' => 'Learn counter shaft transmission assembly',
        'training_content' => 'Counter shaft assembly procedures',
        'method' => 'Hands-on Training',
        'duration' => 16,
        'frequency' => 4,
      ],
      [
        'title' => 'ADVANCE ASSEMBLY OF DIFFERENTIAL',
        'competency_code' => 'MMP001',
        'objective' => 'Master differential assembly techniques',
        'training_content' => 'Differential assembly and adjustment',
        'method' => 'Hands-on Training',
        'duration' => 16,
        'frequency' => 4,
      ],
      [
        'title' => 'ADVANCE ASSEMBLY OF ENGINE PART 2',
        'competency_code' => 'MMP001',
        'objective' => 'Advanced engine assembly techniques',
        'training_content' => 'Engine assembly part 2 procedures',
        'method' => 'Hands-on Training',
        'duration' => 20,
        'frequency' => 5,
      ],
      [
        'title' => 'BASIC QUALITY CONTROL',
        'competency_code' => 'MMP001',
        'objective' => 'Memahami dasar-dasar quality control',
        'training_content' => 'Konsep dasar QC, Jenis-jenis defect, Teknik inspeksi dasar',
        'method' => 'Lecture & Workshop',
        'duration' => 6,
        'frequency' => 2,
      ],
      [
        'title' => 'LEAN MANUFACTURING FUNDAMENTALS',
        'competency_code' => 'BMC001',
        'objective' => 'Mengurangi pemborosan proses, Meningkatkan efisiensi produksi',
        'training_content' => 'Konsep Lean, 7 Waste, Value Stream Mapping',
        'method' => 'Case Study & Simulation',
        'duration' => 12,
        'frequency' => 3,
      ],
      [
        'title' => 'EFFECTIVE TEAM COMMUNICATION',
        'competency_code' => 'BC001',
        'objective' => 'Meningkatkan keterampilan komunikasi',
        'training_content' => 'Prinsip komunikasi efektif, Active listening, Feedback konstruktif',
        'method' => 'Interactive Training',
        'duration' => 8,
        'frequency' => 2,
      ],
      [
        'title' => 'TOTAL PRODUCTIVE MAINTENANCE (TPM)',
        'competency_code' => 'MMP001',
        'objective' => 'Menjaga ketersediaan mesin, Mengurangi downtime',
        'training_content' => 'Konsep TPM, Autonomous maintenance, Planned maintenance',
        'method' => 'Workshop & On-site Practice',
        'duration' => 14,
        'frequency' => 3,
      ],
      [
        'title' => 'BASIC ELECTRICAL TROUBLESHOOTING',
        'competency_code' => 'MMP001',
        'objective' => 'Mampu membaca diagram listrik',
        'training_content' => 'Dasar kelistrikan, Simbol & diagram, Teknik troubleshooting',
        'method' => 'Practical Training',
        'duration' => 10,
        'frequency' => 2,
      ],
      [
        'title' => 'PROBLEM SOLVING & DECISION MAKING',
        'competency_code' => 'BC001',
        'objective' => 'Menguasai teknik pemecahan masalah',
        'training_content' => 'Root cause analysis, Fishbone diagram, 5 Whys technique',
        'method' => 'Workshop & Case Study',
        'duration' => 8,
        'frequency' => 2,
      ],
      [
        'title' => 'WORKPLACE SAFETY & HAZARD AWARENESS',
        'competency_code' => 'BMC001',
        'objective' => 'Meningkatkan kesadaran akan bahaya kerja',
        'training_content' => 'Identifikasi hazard, Safety procedures, PPE usage',
        'method' => 'Lecture & Simulation',
        'duration' => 6,
        'frequency' => 2,
      ],
      [
        'title' => 'ADVANCED WELDING TECHNIQUES',
        'competency_code' => 'MMP001',
        'objective' => 'Menguasai teknik pengelasan lanjut',
        'training_content' => 'TIG & MIG welding, Position welding, Safety welding',
        'method' => 'Hands-on Training',
        'duration' => 20,
        'frequency' => 5,
      ],
      [
        'title' => 'TIME MANAGEMENT & PRODUCTIVITY',
        'competency_code' => 'BC001',
        'objective' => 'Mengelola waktu secara efektif',
        'training_content' => 'Prinsip manajemen waktu, Prioritization matrix, Goal setting',
        'method' => 'Workshop',
        'duration' => 6,
        'frequency' => 2,
      ],
      [
        'title' => 'DIGITAL TRANSFORMATION IN MANUFACTURING',
        'competency_code' => 'BMC002',
        'objective' => 'Memahami konsep digitalisasi',
        'training_content' => 'Industry 4.0 overview, IoT in manufacturing, Data-driven decision',
        'method' => 'Seminar & Group Discussion',
        'duration' => 12,
        'frequency' => 3,
      ],
      [
        'title' => 'LEADERSHIP FUNDAMENTALS',
        'competency_code' => 'LC001',
        'objective' => 'Develop basic leadership skills for team management',
        'training_content' => 'Leadership principles, Team motivation, Delegation skills',
        'method' => 'Workshop & Role Play',
        'duration' => 16,
        'frequency' => 4,
      ],
      [
        'title' => 'MANAGEMENT DEVELOPMENT PROGRAM',
        'competency_code' => 'MDP001',
        'objective' => 'Comprehensive management skill development',
        'training_content' => 'Strategic thinking, Resource management, Change management',
        'method' => 'Intensive Workshop',
        'duration' => 24,
        'frequency' => 6,
      ],
      [
        'title' => 'TRAINING OF TRAINERS (TOT)',
        'competency_code' => 'TOC001',
        'objective' => 'Develop training delivery skills',
        'training_content' => 'Adult learning principles, Training design, Presentation skills',
        'method' => 'Workshop & Practice',
        'duration' => 16,
        'frequency' => 4,
      ],
    ];

    foreach ($modules as $index => $module) {
      $competency = $competencies->get($module['competency_code']);

      $trainingModule = TrainingModule::create([
        'title' => $module['title'],
        'competency_id' => $competency?->id,
        'objective' => $module['objective'],
        'training_content' => $module['training_content'],
        'method' => $module['method'],
        'duration' => $module['duration'],
        'frequency' => $module['frequency'],
      ]);

      // Create pretest and posttest for each module
      $this->createTestsForModule($trainingModule, $index === 0);
    }
  }

  /**
   * Create pretest and posttest for a training module
   */
  private function createTestsForModule(TrainingModule $module, bool $includeEssay = false): void
  {
    $moduleTitle = $module->title;

    // Question templates for pretest
    $pretestQuestions = [
      [
        'text' => "Apa tujuan utama dari pelatihan {$moduleTitle}?",
        'options' => [
          ['text' => 'Meningkatkan efisiensi kerja', 'is_correct' => true],
          ['text' => 'Mengurangi jumlah karyawan', 'is_correct' => false],
          ['text' => 'Menambah beban kerja', 'is_correct' => false],
          ['text' => 'Memperlambat proses produksi', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Sebelum mengikuti {$moduleTitle}, peserta diharapkan memiliki pengetahuan tentang?",
        'options' => [
          ['text' => 'Dasar-dasar pekerjaan di bidang terkait', 'is_correct' => true],
          ['text' => 'Bahasa asing', 'is_correct' => false],
          ['text' => 'Pemrograman komputer', 'is_correct' => false],
          ['text' => 'Akuntansi keuangan', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Apa yang dimaksud dengan continuous improvement dalam konteks {$moduleTitle}?",
        'options' => [
          ['text' => 'Perbaikan berkelanjutan untuk meningkatkan kualitas', 'is_correct' => true],
          ['text' => 'Perubahan besar secara tiba-tiba', 'is_correct' => false],
          ['text' => 'Mempertahankan status quo', 'is_correct' => false],
          ['text' => 'Mengurangi standar kualitas', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Mengapa dokumentasi penting dalam pelaksanaan {$moduleTitle}?",
        'options' => [
          ['text' => 'Untuk melacak progres dan sebagai referensi', 'is_correct' => true],
          ['text' => 'Untuk menambah pekerjaan administrasi', 'is_correct' => false],
          ['text' => 'Tidak ada manfaatnya', 'is_correct' => false],
          ['text' => 'Hanya sebagai formalitas', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Siapa yang bertanggung jawab dalam implementasi {$moduleTitle} di tempat kerja?",
        'options' => [
          ['text' => 'Seluruh tim yang terlibat', 'is_correct' => true],
          ['text' => 'Hanya supervisor', 'is_correct' => false],
          ['text' => 'Hanya departemen HR', 'is_correct' => false],
          ['text' => 'Hanya manajemen atas', 'is_correct' => false],
        ],
      ],
    ];

    // Question templates for posttest
    $posttestQuestions = [
      [
        'text' => "Setelah mengikuti {$moduleTitle}, apa yang harus diterapkan di tempat kerja?",
        'options' => [
          ['text' => 'Menerapkan ilmu yang didapat secara konsisten', 'is_correct' => true],
          ['text' => 'Melupakan semua materi', 'is_correct' => false],
          ['text' => 'Bekerja seperti biasa tanpa perubahan', 'is_correct' => false],
          ['text' => 'Menunggu instruksi dari atasan', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Bagaimana cara mengukur keberhasilan implementasi {$moduleTitle}?",
        'options' => [
          ['text' => 'Melalui KPI dan evaluasi berkala', 'is_correct' => true],
          ['text' => 'Tidak perlu diukur', 'is_correct' => false],
          ['text' => 'Hanya dari laporan bulanan', 'is_correct' => false],
          ['text' => 'Berdasarkan perasaan saja', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Apa langkah pertama dalam menerapkan {$moduleTitle}?",
        'options' => [
          ['text' => 'Melakukan assessment kondisi saat ini', 'is_correct' => true],
          ['text' => 'Langsung implementasi tanpa perencanaan', 'is_correct' => false],
          ['text' => 'Menunggu approval dari semua pihak', 'is_correct' => false],
          ['text' => 'Membeli peralatan baru', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Apa manfaat utama dari {$moduleTitle} bagi perusahaan?",
        'options' => [
          ['text' => 'Meningkatkan produktivitas dan kualitas', 'is_correct' => true],
          ['text' => 'Menambah biaya operasional', 'is_correct' => false],
          ['text' => 'Memperlambat proses kerja', 'is_correct' => false],
          ['text' => 'Tidak ada manfaat signifikan', 'is_correct' => false],
        ],
      ],
      [
        'text' => "Bagaimana cara mempertahankan hasil dari {$moduleTitle}?",
        'options' => [
          ['text' => 'Dengan monitoring dan evaluasi rutin', 'is_correct' => true],
          ['text' => 'Tidak perlu dipertahankan', 'is_correct' => false],
          ['text' => 'Hanya saat ada audit', 'is_correct' => false],
          ['text' => 'Membuat laporan tahunan saja', 'is_correct' => false],
        ],
      ],
    ];

    // Essay questions (only for specific module)
    $essayQuestions = [
      [
        'text' => "Jelaskan dengan kata-kata Anda sendiri, apa yang Anda pahami tentang konsep utama dari {$moduleTitle} dan bagaimana penerapannya di tempat kerja Anda?",
      ],
      [
        'text' => "Berikan contoh nyata bagaimana {$moduleTitle} dapat meningkatkan efisiensi di departemen atau area kerja Anda. Jelaskan langkah-langkah yang akan Anda ambil.",
      ],
      [
        'text' => "Apa tantangan terbesar yang mungkin Anda hadapi dalam mengimplementasikan {$moduleTitle}? Bagaimana strategi Anda untuk mengatasinya?",
      ],
    ];

    // Create Pretest
    $pretest = Test::create([
      'training_module_id' => $module->id,
      'type' => 'pretest',
      'passing_score' => 70,
      'max_attempts' => 3,
      'randomize_question' => true,
    ]);

    // Calculate points for MC questions in pretest
    // If essay included: 3 essays × 20 pts = 60 pts, leaving 40 pts for 5 MC = 8 pts each
    // If no essay: 100 pts for 5 MC = 20 pts each
    $pretestMcPoints = $includeEssay ? 8 : 20;

    foreach ($pretestQuestions as $order => $questionData) {
      $question = TestQuestion::create([
        'test_id' => $pretest->id,
        'question_type' => 'multiple',
        'text' => $questionData['text'],
        'order' => $order,
        'max_points' => $pretestMcPoints,
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

    // Create Posttest
    $posttest = Test::create([
      'training_module_id' => $module->id,
      'type' => 'posttest',
      'passing_score' => 75,
      'max_attempts' => 3,
      'randomize_question' => true,
    ]);

    // Calculate points for MC questions in posttest
    // If essay included: 3 essays × 20 pts = 60 pts, leaving 40 pts for 5 MC = 8 pts each
    // If no essay: 100 pts for 5 MC = 20 pts each
    $posttestMcPoints = $includeEssay ? 8 : 20;

    foreach ($posttestQuestions as $order => $questionData) {
      $question = TestQuestion::create([
        'test_id' => $posttest->id,
        'question_type' => 'multiple',
        'text' => $questionData['text'],
        'order' => $order,
        'max_points' => $posttestMcPoints,
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

    // Add essay questions for specific module
    // 3 essay questions × 20 points each = 60 points total
    // Leaving 40 points for 5 MC questions = 8 points each (calculated above)
    if ($includeEssay) {
      foreach ($essayQuestions as $order => $questionData) {
        // Add to pretest
        TestQuestion::create([
          'test_id' => $pretest->id,
          'question_type' => 'essay',
          'text' => $questionData['text'],
          'order' => count($pretestQuestions) + $order,
          'max_points' => 20,
        ]);

        // Add to posttest
        TestQuestion::create([
          'test_id' => $posttest->id,
          'question_type' => 'essay',
          'text' => $questionData['text'],
          'order' => count($posttestQuestions) + $order,
          'max_points' => 20,
        ]);
      }
    }
  }
}
