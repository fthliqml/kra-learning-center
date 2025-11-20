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
            // 6 Approved Certifications (with complete data: sessions, participants, attendance, scores)
            [
                'module_id' => $modules->random()->id,
                'name' => 'Welding Inspector Batch 2024-Q1',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(3)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(3),
                'participants_count' => 8,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'ISO 9001 Lead Auditor Program 2024',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(4)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(4),
                'participants_count' => 10,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Forklift Operator Training Batch 15',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(5)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(5),
                'participants_count' => 6,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Electrical Safety Professional 2024',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(3)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(3),
                'participants_count' => 7,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Six Sigma Green Belt Batch 8',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(4)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(4),
                'participants_count' => 12,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Quality Control Fundamentals Q1',
                'status' => 'approved',
                'approved_at' => Carbon::now()->subMonths(2)->addDays(rand(1, 5)),
                'created_at' => Carbon::now()->subMonths(2),
                'participants_count' => 9,
            ],

            // 6 Completed Certifications (pending approval - with sessions, participants, attendance, scores)
            [
                'module_id' => $modules->random()->id,
                'name' => 'Lean Six Sigma Black Belt 2024',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(15),
                'participants_count' => 8,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Maintenance & Reliability Professional Training',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(20),
                'participants_count' => 10,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'ISO Internal Auditor Certification Batch 5',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(10),
                'participants_count' => 7,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Project Management Professional 2024-Q4',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(25),
                'participants_count' => 11,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Equipment Maintenance Training Q3',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(18),
                'participants_count' => 9,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Advanced Welding Certification Q4',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(12),
                'participants_count' => 6,
            ],

            // 3 Scheduled Certifications (with future sessions and participants only)
            [
                'module_id' => $modules->random()->id,
                'name' => 'Safety Management Certification Q1',
                'status' => 'scheduled',
                'created_at' => Carbon::now()->subDays(5),
                'participants_count' => 8,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'NEBOSH Safety Certificate Program',
                'status' => 'scheduled',
                'created_at' => Carbon::now()->subDays(8),
                'participants_count' => 10,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Equipment Analysis Certification',
                'status' => 'scheduled',
                'created_at' => Carbon::now()->subDays(3),
                'participants_count' => 7,
            ],

            // 3 Rejected/Cancelled Certifications (no additional data)
            [
                'module_id' => $modules->random()->id,
                'name' => 'Quality Control Specialist Batch 3',
                'status' => 'rejected',
                'created_at' => Carbon::now()->subMonths(2),
                'participants_count' => 0,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Production Management Certification 2024',
                'status' => 'cancelled',
                'created_at' => Carbon::now()->subMonth(),
                'participants_count' => 0,
            ],
            [
                'module_id' => $modules->random()->id,
                'name' => 'Equipment Maintenance Training Q2',
                'status' => 'rejected',
                'created_at' => Carbon::now()->subMonths(3),
                'participants_count' => 0,
            ],
        ];

        foreach ($certifications as $index => $certData) {
            $participantsCount = $certData['participants_count'];
            unset($certData['participants_count']);

            $certification = Certification::create($certData);

            // For approved and completed certifications: create full data
            if (in_array($certification->status, ['approved', 'completed'])) {
                // Theory session (happened first)
                $theoryDate = $certification->status === 'approved'
                    ? Carbon::parse($certification->created_at)->addDays(rand(3, 7))
                    : Carbon::now()->subDays(rand(5, 10));

                $theorySession = CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'theory',
                    'date' => $theoryDate,
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                    'location' => 'Training Room ' . rand(1, 5),
                ]);

                // Practical session (1-2 weeks after theory)
                $practicalDate = Carbon::parse($theoryDate)->addDays(rand(7, 14));

                $practicalSession = CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'practical',
                    'date' => $practicalDate,
                    'start_time' => '13:00:00',
                    'end_time' => '17:00:00',
                    'location' => 'Workshop Area ' . rand(1, 3),
                ]);

                // Create participants with realistic scores
                $selectedEmployees = $employees->random(min($participantsCount, $employees->count()));

                foreach ($selectedEmployees as $employee) {
                    $participant = CertificationParticipant::create([
                        'certification_id' => $certification->id,
                        'employee_id' => $employee->id,
                        'assigned_at' => Carbon::parse($certification->created_at)->subDays(rand(5, 15)),
                    ]);

                    // Theory attendance (100% present for completed/approved certifications)
                    $theoryAttendance = 'present';
                    CertificationAttendance::create([
                        'participant_id' => $participant->id,
                        'session_id' => $theorySession->id,
                        'status' => $theoryAttendance,
                        'absence_notes' => null,
                        'recorded_at' => Carbon::parse($theorySession->date)->setTimeFromTimeString($theorySession->start_time),
                    ]);

                    // Practical attendance (100% present for completed/approved certifications)
                    $practicalAttendance = 'present';
                    CertificationAttendance::create([
                        'participant_id' => $participant->id,
                        'session_id' => $practicalSession->id,
                        'status' => $practicalAttendance,
                        'absence_notes' => null,
                        'recorded_at' => Carbon::parse($practicalSession->date)->setTimeFromTimeString($practicalSession->start_time),
                    ]);

                    // Create realistic scores - both theory and practical
                    // 70% pass rate for theory
                    $theoryScore = rand(1, 100) <= 70 ? rand(70, 95) : rand(50, 69);
                    CertificationScore::create([
                        'participant_id' => $participant->id,
                        'session_id' => $theorySession->id,
                        'score' => $theoryScore,
                        'status' => $theoryScore >= 70 ? 'passed' : 'failed',
                        'recorded_at' => Carbon::parse($theorySession->date)->addHours(4),
                    ]);

                    // 75% pass rate for practical
                    $practicalScore = rand(1, 100) <= 75 ? rand(75, 98) : rand(55, 74);
                    CertificationScore::create([
                        'participant_id' => $participant->id,
                        'session_id' => $practicalSession->id,
                        'score' => $practicalScore,
                        'status' => $practicalScore >= 75 ? 'passed' : 'failed',
                        'recorded_at' => Carbon::parse($practicalSession->date)->addHours(4),
                    ]);
                }
            }

            // For scheduled certifications: create future sessions and participants only
            if ($certification->status === 'scheduled') {
                // Future theory session
                $futureTheoryDate = Carbon::now()->addDays(rand(10, 30));
                CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'theory',
                    'date' => $futureTheoryDate,
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                    'location' => 'Training Room ' . rand(1, 5),
                ]);

                // Future practical session (1-2 weeks after theory)
                CertificationSession::create([
                    'certification_id' => $certification->id,
                    'type' => 'practical',
                    'date' => Carbon::parse($futureTheoryDate)->addDays(rand(7, 14)),
                    'start_time' => '13:00:00',
                    'end_time' => '17:00:00',
                    'location' => 'Workshop Area ' . rand(1, 3),
                ]);

                // Assign participants
                $selectedEmployees = $employees->random(min($participantsCount, $employees->count()));
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
        $this->command->info('- 6 approved certifications with complete participant data');
        $this->command->info('- 6 completed certifications (pending approval) with scores');
        $this->command->info('- 3 scheduled certifications with future sessions');
        $this->command->info('- 3 rejected/cancelled certifications');
    }
}
