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
                'title' => 'Evaluasi Pelatihan Operator Excavator',
                'description' => 'Survei untuk menilai efektivitas pelatihan operator alat berat.',
                'status' => 'active',
                'level' => 1,
            ],
            [
                'title' => 'Survei Kepatuhan Safety Pekerja Lapangan',
                'description' => 'Menilai kepatuhan terhadap standar keselamatan di area kerja.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Feedback Program Kaizen Mingguan',
                'description' => 'Mengukur partisipasi dan efektivitas program continuous improvement.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Evaluasi Kinerja Tim Produksi',
                'description' => 'Digunakan untuk mengukur performa produksi per divisi.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Kepuasan Layanan After-Sales',
                'description' => 'Survei untuk pelanggan terkait pelayanan purna jual Komatsu.',
                'status' => 'draft',
                'level' => 2,
            ],
            [
                'title' => 'Penilaian Program K3 (Keselamatan dan Kesehatan Kerja)',
                'description' => 'Survei tahunan untuk memonitor implementasi kebijakan K3.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Survey Kepuasan Karyawan Pabrik Cibitung',
                'description' => 'Mengukur tingkat kepuasan karyawan terhadap lingkungan kerja.',
                'status' => 'draft',
                'level' => 2,
            ],
            [
                'title' => 'Evaluasi Workshop Maintenance Alat Berat',
                'description' => 'Menilai efektivitas workshop pemeliharaan mesin dan alat berat.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survei Inovasi Teknologi Produksi',
                'description' => 'Mendapatkan umpan balik terkait implementasi teknologi baru di lini produksi.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Feedback Training Leadership Supervisor',
                'description' => 'Survei untuk mengevaluasi efektivitas pelatihan kepemimpinan supervisor.',
                'status' => 'draft',
                'level' => 2,
            ],
            [
                'title' => 'Survei Efektivitas Program 5S Area Produksi',
                'description' => 'Menilai penerapan prinsip 5S (Seiri, Seiton, Seiso, Seiketsu, Shitsuke) di area kerja.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Evaluasi Program Zero Accident',
                'description' => 'Survei internal untuk memantau capaian target Zero Accident tiap departemen.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Survey Kepuasan Vendor dan Supplier',
                'description' => 'Menilai hubungan kerja dan kepuasan vendor terhadap proses pengadaan Komatsu.',
                'status' => 'draft',
                'level' => 2,
            ],
            [
                'title' => 'Feedback Program Digital Transformation',
                'description' => 'Mendapatkan pandangan karyawan tentang penerapan sistem digitalisasi di pabrik.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Evaluasi Pelatihan Mekanik Magang',
                'description' => 'Survei untuk mengukur kompetensi peserta pelatihan mekanik baru.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survei Efisiensi Proses Logistik Internal',
                'description' => 'Menilai kecepatan dan keakuratan distribusi komponen antar divisi.',
                'status' => 'draft',
                'level' => 2,
            ],
            [
                'title' => 'Feedback Program CSR Komatsu Peduli',
                'description' => 'Mengumpulkan tanggapan karyawan terkait kegiatan sosial perusahaan.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Evaluasi Sistem Penilaian Kinerja Baru',
                'description' => 'Menilai penerapan sistem KPI dan appraisal yang diperbarui tahun ini.',
                'status' => 'draft',
                'level' => 3,
            ],
            [
                'title' => 'Survey Lingkungan dan Kebersihan Area Produksi',
                'description' => 'Menilai tingkat kebersihan, kerapian, dan kenyamanan area kerja.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Feedback Program Pelatihan Safety Awareness',
                'description' => 'Mengukur tingkat kesadaran dan pemahaman pekerja terhadap risiko kerja.',
                'status' => 'draft',
                'level' => 2,
            ],
            [
                'title' => 'Survei Kebersihan Area Kantor dan Bengkel',
                'description' => 'Menilai kondisi kebersihan dan kenyamanan area kerja sehari-hari.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Feedback Briefing Pagi Divisi Produksi',
                'description' => 'Mengumpulkan umpan balik harian terkait komunikasi dalam briefing pagi.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survei Kedisiplinan Kehadiran Karyawan',
                'description' => 'Memantau tingkat kedisiplinan dan keteraturan jam kerja karyawan.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Feedback Penggunaan APD di Area Kerja',
                'description' => 'Menilai kepatuhan terhadap penggunaan alat pelindung diri (APD).',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survei Efektivitas Papan Informasi Produksi',
                'description' => 'Menilai apakah papan informasi di area kerja mudah dipahami oleh tim.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Evaluasi Program Morning Exercise',
                'description' => 'Survei kepuasan terhadap kegiatan peregangan pagi di pabrik.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Feedback Fasilitas Kantin Karyawan',
                'description' => 'Menilai kualitas makanan dan kebersihan kantin perusahaan.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survei Kepuasan Fasilitas Parkir',
                'description' => 'Menilai ketersediaan dan kenyamanan area parkir bagi karyawan.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Feedback Kegiatan Sharing Session',
                'description' => 'Mengumpulkan pendapat peserta terkait manfaat sharing session mingguan.',
                'status' => 'draft',
                'level' => 1,
            ],
            [
                'title' => 'Survei Penerapan Etika Kerja Harian',
                'description' => 'Menilai pemahaman dan penerapan nilai-nilai profesional di tempat kerja.',
                'status' => 'draft',
                'level' => 1,
            ],
        ];

        SurveyTemplate::insert($templates);

        // Ambil satu template untuk contoh seeding pertanyaan dan opsi
        $template = \App\Models\SurveyTemplate::first();
        if ($template) {
            // Pertanyaan 1 (multiple choice)
            $q1 = \App\Models\SurveyTemplateQuestion::create([
                'survey_template_id' => $template->id,
                'text' => 'How satisfied are you with your job?',
                'question_type' => 'multiple',
                'order' => 1,
            ]);
            foreach ([
                'Very Satisfied',
                'Satisfied',
                'Neutral',
                'Dissatisfied',
                'Very Dissatisfied',
            ] as $i => $opt) {
                \App\Models\SurveyTemplateOption::create([
                    'survey_template_question_id' => $q1->id,
                    'text' => $opt,
                    'order' => $i + 1,
                ]);
            }

            // Pertanyaan 2 (essay)
            \App\Models\SurveyTemplateQuestion::create([
                'survey_template_id' => $template->id,
                'text' => 'What would you improve in the workplace?',
                'question_type' => 'essay',
                'order' => 2,
            ]);
        }
    }
}
