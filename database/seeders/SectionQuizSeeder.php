<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Section;
use App\Models\SectionQuizQuestion;
use App\Models\SectionQuizQuestionOption;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk menambahkan quiz ke setiap course.
 * Setiap course akan memiliki 1 quiz dengan 5 soal multiple choice
 * di section pertama dari topic pertama.
 */
class SectionQuizSeeder extends Seeder
{
    /**
     * Bank soal quiz berbasis topik course.
     * Format: 'keyword' => [['question', 'options' => [...], 'correct' => index], ...]
     */
    protected array $questionBank = [
        'safety' => [
            ['question' => 'Apa langkah pertama yang harus dilakukan saat menemukan bahaya di tempat kerja?', 'options' => ['Abaikan dan lanjutkan bekerja', 'Laporkan ke supervisor', 'Posting di media sosial', 'Tunggu orang lain yang lapor'], 'correct' => 1],
            ['question' => 'APD singkatan dari apa?', 'options' => ['Alat Pelindung Diri', 'Alat Pengaman Darurat', 'Asosiasi Pekerja Daerah', 'Aturan Perlindungan Dasar'], 'correct' => 0],
            ['question' => 'Kapan safety briefing sebaiknya dilakukan?', 'options' => ['Setelah kecelakaan', 'Sekali setahun', 'Sebelum memulai pekerjaan', 'Saat istirahat'], 'correct' => 2],
            ['question' => 'Warna helm safety untuk supervisor biasanya?', 'options' => ['Putih', 'Kuning', 'Merah', 'Hijau'], 'correct' => 0],
            ['question' => 'Apa yang dimaksud dengan near miss?', 'options' => ['Kecelakaan fatal', 'Kejadian hampir celaka', 'Prosedur keselamatan', 'Laporan bulanan'], 'correct' => 1],
        ],
        'quality' => [
            ['question' => 'Apa tujuan utama quality control?', 'options' => ['Mengurangi biaya produksi', 'Memastikan produk sesuai standar', 'Mempercepat produksi', 'Mengurangi jumlah pekerja'], 'correct' => 1],
            ['question' => 'QC dan QA memiliki perbedaan utama pada?', 'options' => ['Gaji karyawan', 'Fokus pencegahan vs deteksi', 'Jumlah tim', 'Lokasi kerja'], 'correct' => 1],
            ['question' => 'Diagram Pareto digunakan untuk?', 'options' => ['Mengukur waktu', 'Mengidentifikasi 20% penyebab 80% masalah', 'Menghitung gaji', 'Membuat jadwal'], 'correct' => 1],
            ['question' => 'Apa itu defect rate?', 'options' => ['Kecepatan produksi', 'Persentase produk cacat', 'Jumlah karyawan absen', 'Biaya material'], 'correct' => 1],
            ['question' => 'First Time Right (FTR) berarti?', 'options' => ['Produk dibuat dengan cepat', 'Produk benar sejak pertama dibuat', 'Produk mahal', 'Produk langka'], 'correct' => 1],
        ],
        'lean' => [
            ['question' => 'Berapa jumlah waste dalam konsep Lean?', 'options' => ['5', '7', '10', '12'], 'correct' => 1],
            ['question' => 'Apa itu Kaizen?', 'options' => ['Nama perusahaan Jepang', 'Perbaikan berkelanjutan', 'Jenis mesin', 'Standar kualitas'], 'correct' => 1],
            ['question' => '5S terdiri dari?', 'options' => ['Sort, Set, Shine, Standardize, Sustain', 'Start, Stop, Stay, Stand, Sit', 'See, Say, Show, Share, Smile', 'Speed, Safety, Service, Standard, Success'], 'correct' => 0],
            ['question' => 'Value Stream Mapping digunakan untuk?', 'options' => ['Menggambar peta', 'Memetakan aliran nilai dalam proses', 'Membuat laporan keuangan', 'Merekrut karyawan'], 'correct' => 1],
            ['question' => 'Muda dalam bahasa Jepang berarti?', 'options' => ['Muda (usia)', 'Pemborosan', 'Kualitas', 'Kecepatan'], 'correct' => 1],
        ],
        'communication' => [
            ['question' => 'Komponen komunikasi yang paling penting adalah?', 'options' => ['Suara keras', 'Mendengarkan aktif', 'Berbicara cepat', 'Menggunakan jargon'], 'correct' => 1],
            ['question' => 'Apa itu feedback dalam komunikasi?', 'options' => ['Umpan balik', 'Kritik negatif', 'Perintah atasan', 'Instruksi kerja'], 'correct' => 0],
            ['question' => 'Komunikasi non-verbal meliputi?', 'options' => ['Email', 'Bahasa tubuh', 'Telepon', 'Surat resmi'], 'correct' => 1],
            ['question' => 'Barrier komunikasi yang sering terjadi adalah?', 'options' => ['Kejelasan pesan', 'Asumsi dan prasangka', 'Mendengarkan aktif', 'Feedback positif'], 'correct' => 1],
            ['question' => 'Komunikasi asertif adalah?', 'options' => ['Agresif dan memaksa', 'Tegas namun menghargai orang lain', 'Pasif dan diam', 'Manipulatif'], 'correct' => 1],
        ],
        'leadership' => [
            ['question' => 'Apa ciri pemimpin yang efektif?', 'options' => ['Otoriter', 'Mampu menginspirasi tim', 'Bekerja sendiri', 'Menghindari tanggung jawab'], 'correct' => 1],
            ['question' => 'Servant leadership berfokus pada?', 'options' => ['Kekuasaan pemimpin', 'Melayani dan mendukung tim', 'Profit perusahaan saja', 'Kompetisi internal'], 'correct' => 1],
            ['question' => 'Delegasi yang efektif membutuhkan?', 'options' => ['Kontrol total', 'Kepercayaan dan kejelasan tugas', 'Micromanagement', 'Menunda keputusan'], 'correct' => 1],
            ['question' => 'Emotional intelligence dalam kepemimpinan berarti?', 'options' => ['IQ tinggi', 'Kemampuan mengelola emosi diri dan orang lain', 'Pandai matematika', 'Tidak punya emosi'], 'correct' => 1],
            ['question' => 'Visionary leader adalah pemimpin yang?', 'options' => ['Fokus pada masa lalu', 'Memiliki visi jelas untuk masa depan', 'Menghindari perubahan', 'Takut mengambil risiko'], 'correct' => 1],
        ],
        'default' => [
            ['question' => 'Apa tujuan utama dari training?', 'options' => ['Membuang waktu', 'Meningkatkan kompetensi karyawan', 'Mengurangi gaji', 'Menambah beban kerja'], 'correct' => 1],
            ['question' => 'Lifelong learning berarti?', 'options' => ['Belajar sampai SMA', 'Belajar sepanjang hayat', 'Belajar hanya di kantor', 'Tidak perlu belajar'], 'correct' => 1],
            ['question' => 'Apa manfaat dari self-assessment?', 'options' => ['Mengetahui kekuatan dan kelemahan diri', 'Menyalahkan orang lain', 'Menghindari evaluasi', 'Tidak ada manfaat'], 'correct' => 0],
            ['question' => 'Growth mindset adalah?', 'options' => ['Keyakinan bahwa kemampuan bisa dikembangkan', 'Berpikir bahwa bakat adalah segalanya', 'Menolak kritik', 'Fixed mindset'], 'correct' => 0],
            ['question' => 'Continuous improvement bertujuan untuk?', 'options' => ['Mempertahankan status quo', 'Terus memperbaiki proses', 'Mengurangi kualitas', 'Menghindari perubahan'], 'correct' => 1],
        ],
    ];

    public function run(): void
    {
        $courses = Course::with(['learningModules.sections'])->get();

        foreach ($courses as $course) {
            // Get first topic with sections
            $firstTopic = $course->learningModules->first();
            if (!$firstTopic) {
                continue; // No topics, skip
            }

            // Get first section of first topic
            $firstSection = $firstTopic->sections->first();
            if (!$firstSection) {
                continue; // No sections, skip
            }

            // Check if quiz already exists for this section
            $existingQuizCount = SectionQuizQuestion::where('section_id', $firstSection->id)->count();
            if ($existingQuizCount > 0) {
                continue; // Quiz already exists, skip
            }

            // Enable quiz on the section
            $firstSection->update(['is_quiz_on' => true]);

            // Select questions based on course title keywords
            $questions = $this->selectQuestionsForCourse($course->title);

            // Create quiz questions with options
            foreach ($questions as $order => $q) {
                $questionModel = SectionQuizQuestion::create([
                    'section_id' => $firstSection->id,
                    'type' => 'multiple',
                    'question' => $q['question'],
                    'order' => $order,
                ]);

                // Create options
                foreach ($q['options'] as $optIndex => $optText) {
                    SectionQuizQuestionOption::create([
                        'question_id' => $questionModel->id,
                        'option' => $optText,
                        'is_correct' => $optIndex === $q['correct'],
                        'order' => $optIndex,
                    ]);
                }
            }
        }
    }

    /**
     * Select 5 questions based on course title keywords.
     */
    protected function selectQuestionsForCourse(string $title): array
    {
        $titleLower = strtolower($title);

        // Match keywords to question bank
        if (str_contains($titleLower, 'safety')) {
            return $this->questionBank['safety'];
        }
        if (str_contains($titleLower, 'quality')) {
            return $this->questionBank['quality'];
        }
        if (str_contains($titleLower, 'lean')) {
            return $this->questionBank['lean'];
        }
        if (str_contains($titleLower, 'communication') || str_contains($titleLower, 'komunikasi')) {
            return $this->questionBank['communication'];
        }
        if (str_contains($titleLower, 'leadership') || str_contains($titleLower, 'leader')) {
            return $this->questionBank['leadership'];
        }

        // Default questions for other courses
        return $this->questionBank['default'];
    }
}
