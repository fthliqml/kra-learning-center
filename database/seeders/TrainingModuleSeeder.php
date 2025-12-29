<?php

namespace Database\Seeders;

use App\Models\Competency;
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

        foreach ($modules as $module) {
            $competency = $competencies->get($module['competency_code']);

            TrainingModule::create([
                'title' => $module['title'],
                'competency_id' => $competency?->id,
                'objective' => $module['objective'],
                'training_content' => $module['training_content'],
                'method' => $module['method'],
                'duration' => $module['duration'],
                'frequency' => $module['frequency'],
            ]);
        }
    }
}
