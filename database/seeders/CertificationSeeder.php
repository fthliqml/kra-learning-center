<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\CertificationAttendance;
use App\Models\CertificationModule;
use App\Models\CertificationParticipant;
use App\Models\CertificationScore;
use App\Models\CertificationSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = CertificationModule::all();
        $employees = User::whereIn('role', ['employee', 'spv'])->get();

        if ($modules->isEmpty() || $employees->isEmpty()) {
            $this->command->warn('Make sure CertificationModule and User seeders run first!');
            return;
        }

        // Data certifications dengan status yang bervariasi
        $certifications = [
            // 6 Approved Certifications
            [
                'module_id' => $modules->random()->id,
                'name' => 'Welding Inspector Batch 2024-Q1',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(3)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(3),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'ISO 9001 Lead Auditor Program 2024',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(4)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(4),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Safety Management Certification Q1',
                'status' => 'scheduled',
                'created_at' => Carbon::now()->subMonths(2),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Forklift Operator Training Batch 15',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(5)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(5),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Electrical Safety Professional 2024',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(3)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(3),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Six Sigma Green Belt Batch 8',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(4)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(4),
            ],

            // 6 Scheduled (Pending) Certifications
            [
                'module_id' => $modules->random()->id,
                'name' => 'Lean Six Sigma Black Belt 2024',
                'status' => 'scheduled',
                'created_at' => Carbon::now()->subDays(15),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Maintenance & Reliability Professional Training',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(20),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'ISO Internal Auditor Certification Batch 5',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(10),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Project Management Professional 2024-Q4',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(25),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'NEBOSH Safety Certificate Program',
                'status' => 'scheduled',
                'created_at' => Carbon::now()->subDays(8),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Advanced Welding Certification Q4',
                'status' => 'scheduled',
                'created_at' => Carbon::now()->subDays(12),
            ],

            // 3 Rejected/Cancelled Certifications
            [
                'module_id' => $modules->random()->id,
                'name' => 'Quality Control Specialist Batch 3',
                'status' => 'rejected',
                'created_at' => Carbon::now()->subMonths(2),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Production Management Certification 2024',
                'status' => 'cancelled',
                'created_at' => Carbon::now()->subMonth(),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Equipment Maintenance Training Q2',
                'status' => 'rejected',
                'created_at' => Carbon::now()->subMonths(3),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Equipment Maintenance Training Q3',
                'status' => 'completed',
                'created_at' => Carbon::now()->subMonths(3),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Equipment Analysis',
                'status' => 'completed',
                'created_at' => Carbon::now()->subMonths(3),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Tools Maintenance Training Q5',
                'status' => 'completed',
                'created_at' => Carbon::now()->subMonths(3),
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Equipment Management Training Q2',
                'status' => 'completed',
                'created_at' => Carbon::now()->subMonths(3),
            ],
        ];

        foreach ($certifications as $index => $certData) {
            $certification = Certification::create($certData);

            // Hanya buat sessions, participants, attendance & scores untuk approved certifications
            if ($certification->status === 'approved') {
                // Buat 2 sessions (theory & practical)
                $theorySession = CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'theory',
                    'date' => Carbon::now()->subMonths(2)->addDays($index),
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                    'location' => 'Training Room ' . rand(1, 5),
                ]);

                $practicalSession = CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'practical',
                    'date' => Carbon::now()->subMonths(2)->addDays($index + 1),
                    'start_time' => '13:00:00',
                    'end_time' => '17:00:00',
                    'location' => 'Workshop Area ' . rand(1, 3),
                ]);

                // Buat 3-5 participants per certification
                $participantCount = rand(3, 5);
                $selectedEmployees = $employees->random($participantCount);

                foreach ($selectedEmployees as $employee) {
                    $participant = CertificationParticipant::create([
                        'certification_id' => $certification->id,
                        'employee_id' => $employee->id,
                        'assigned_at' => Carbon::now()->subMonths(2)->subDays(rand(5, 15)),
                    ]);

                    // Buat attendance untuk theory session
                    $theoryAttendance = rand(0, 100) > 10 ? 'present' : 'absent';
                    CertificationAttendance::create([
                        'participant_id' => $participant->id,
                        'session_id' => $theorySession->id,
                        'status' => $theoryAttendance,
                        'absence_notes' => $theoryAttendance === 'absent' ? 'Sick leave' : null,
                        'recorded_at' => Carbon::parse($theorySession->date)->setTimeFromTimeString($theorySession->start_time),
                    ]);

                    // Buat attendance untuk practical session
                    $practicalAttendance = rand(0, 100) > 10 ? 'present' : 'absent';
                    CertificationAttendance::create([
                        'participant_id' => $participant->id,
                        'session_id' => $practicalSession->id,
                        'status' => $practicalAttendance,
                        'absence_notes' => $practicalAttendance === 'absent' ? 'Emergency' : null,
                        'recorded_at' => Carbon::parse($practicalSession->date)->setTimeFromTimeString($practicalSession->start_time),
                    ]);

                    // Buat scores jika hadir
                    if ($theoryAttendance === 'present') {
                        $theoryScore = rand(60, 100);
                        CertificationScore::create([
                            'participant_id' => $participant->id,
                            'session_id' => $theorySession->id,
                            'score' => $theoryScore,
                            'status' => $theoryScore >= 75 ? 'passed' : 'failed',
                            'recorded_at' => Carbon::parse($theorySession->date)->addHours(4),
                        ]);
                    }

                    if ($practicalAttendance === 'present') {
                        $practicalScore = rand(65, 100);
                        CertificationScore::create([
                            'participant_id' => $participant->id,
                            'session_id' => $practicalSession->id,
                            'score' => $practicalScore,
                            'status' => $practicalScore >= 80 ? 'passed' : 'failed',
                            'recorded_at' => Carbon::parse($practicalSession->date)->addHours(4),
                        ]);
                    }
                }
            }

            // Untuk scheduled certifications, buat sessions dan participants saja (tanpa attendance/scores)
            if ($certification->status === 'scheduled') {
                // Buat future sessions
                CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'theory',
                    'date' => Carbon::now()->addDays(rand(10, 30)),
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                    'location' => 'Training Room ' . rand(1, 5),
                ]);

                CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'practical',
                    'date' => Carbon::now()->addDays(rand(31, 45)),
                    'start_time' => '13:00:00',
                    'end_time' => '17:00:00',
                    'location' => 'Workshop Area ' . rand(1, 3),
                ]);

                // Buat participants untuk scheduled certification
                $participantCount = rand(4, 6);
                $selectedEmployees = $employees->random($participantCount);

                foreach ($selectedEmployees as $employee) {
                    CertificationParticipant::create([
                        'certification_id' => $certification->id,
                        'employee_id' => $employee->id,
                        'assigned_at' => Carbon::now()->subDays(rand(1, 7)),
                    ]);
                }
            }
        }

        $this->command->info('Certification seeding completed successfully!');
        $this->command->info('- 6 approved certifications with complete data');
        $this->command->info('- 6 scheduled certifications with sessions and participants');
        $this->command->info('- 3 rejected/cancelled certifications');
    }
}
