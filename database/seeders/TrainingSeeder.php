<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\TrainingModule;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Get competencies for assigning to trainers and modules
        $competencies = Competency::all();
        if ($competencies->isEmpty()) {
            $this->command->warn('No competencies found. Please run CompetencySeeder first.');
            return;
        }

        // 1. Ensure trainers exist
        // First trainer: internal user (instructor@example.com) - specializes in BMC & TOC
        $instructorUser = User::where('email', 'instructor@example.com')->first();
        $trainer = Trainer::create([
            'user_id' => $instructorUser?->id,
            'institution' => 'KRA',
        ]);

        // Second trainer: external (no user_id) - specializes in Safety (BMC)
        $trainer2 = Trainer::create([
            'user_id' => null,
            'name' => 'Ahmad Fauzi',
            'institution' => 'PT. Safety Training Indonesia',
        ]);

        // Third trainer: external (no user_id) - specializes in MMP
        $trainer3 = Trainer::create([
            'user_id' => null,
            'name' => 'Budi Santoso',
            'institution' => 'Lembaga Sertifikasi Profesi',
        ]);

        // 2. Employees pool for assessments/attendances
        $employees = User::inRandomOrder()->limit(10)->get();
        $employeeUser = User::where('email', 'employee@example.com')->first();
        if ($employeeUser && !$employees->contains('id', $employeeUser->id)) {
            $employees->pop(); // remove one to make space
            $employees->push($employeeUser);
        }

        // Get a default competency for fallback (prefer specific BMC code, otherwise first competency)
        $defaultCompetency = $competencies->where('code', 'BMC003')->first()
            ?? $competencies->first();

        // Get training modules for IN-HOUSE type
        $trainingModules = TrainingModule::with('competency')->get();
        if ($trainingModules->isEmpty()) {
            // If no modules exist, create at least one with proper competency
            $trainingModules = collect([
                TrainingModule::create([
                    'title' => 'Safety Induction Module',
                    'competency_id' => $defaultCompetency->id,
                    'objective' => 'Basic safety training',
                    'training_content' => 'Safety procedures and protocols',
                    'method' => 'Classroom',
                    'duration' => 8,
                    'frequency' => 1,
                ])
            ]);
        }

        // 3. Create 6 trainings with sessions, assessments, and attendances
        for ($i = 1; $i <= 6; $i++) {
            $start = Carbon::now()->addDays(($i - 1) * 7); // stagger by weeks
            $end = $start->copy()->addDays(3);

            // Pick a random training module
            $module = $trainingModules->random();

            $training = Training::create([
                'name' => 'Safety Induction #' . $i,
                'type' => 'IN',
                'module_id' => $module->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'in_progress',
            ]);

            // Sessions for this training
            $sessions = [];
            $dayNumber = 1;
            foreach (CarbonPeriod::create($training->start_date, $training->end_date) as $date) {
                $sessions[] = TrainingSession::create([
                    'training_id' => $training->id,
                    'trainer_id' => $trainer->id,
                    'room_name' => 'Wakatobi',
                    'date' => $date->toDateString(),
                    'room_location' => "EDC {$i}-{$dayNumber}",
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'day_number' => $dayNumber,
                    'status' => 'in_progress',
                ]);
                $dayNumber++;
            }

            // Assessments (participants) for this training
            foreach ($employees as $employee) {
                TrainingAssessment::create([
                    'training_id' => $training->id,
                    'employee_id' => $employee->id,
                ]);
            }

            // Attendances for each session and participant
            foreach ($sessions as $session) {
                foreach ($employees as $employee) {
                    TrainingAttendance::create([
                        'session_id' => $session->id,
                        'employee_id' => $employee->id,
                        'notes' => null,
                        'recorded_at' => Carbon::now(),
                    ]);
                }
            }
        }

        // 4. Create BLENDED trainings (Course + offline sessions)
        $courses = \App\Models\Course::with('competency')->get();
        if ($courses->isNotEmpty()) {
            // Create 2 BLENDED trainings
            foreach ($courses->take(2) as $index => $course) {
                $start = Carbon::now()->addDays(50 + ($index * 14)); // stagger by 2 weeks
                $end = $start->copy()->addDays(2); // 3 days of offline sessions

                $blendedTraining = Training::create([
                    'name' => $course->title, // BLENDED uses course title
                    'type' => 'BLENDED',
                    'course_id' => $course->id,
                    'module_id' => null,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => 'in_progress',
                ]);

                // Create offline sessions for BLENDED training
                $blendedSessions = [];
                $blendedDayNumber = 1;
                $availableTrainers = [$trainer, $trainer2, $trainer3];
                
                foreach (CarbonPeriod::create($blendedTraining->start_date, $blendedTraining->end_date) as $date) {
                    $blendedSessions[] = TrainingSession::create([
                        'training_id' => $blendedTraining->id,
                        'trainer_id' => $availableTrainers[array_rand($availableTrainers)]->id,
                        'room_name' => 'Ruang Blended Learning',
                        'date' => $date->toDateString(),
                        'room_location' => 'Gedung Training Lt. 2',
                        'start_time' => '13:00:00',
                        'end_time' => '16:00:00',
                        'day_number' => $blendedDayNumber,
                        'status' => 'in_progress',
                    ]);
                    $blendedDayNumber++;
                }

                // Assessments for BLENDED training (assign same employees)
                foreach ($employees->take(5) as $employee) {
                    TrainingAssessment::create([
                        'training_id' => $blendedTraining->id,
                        'employee_id' => $employee->id,
                    ]);
                }

                // Attendances for BLENDED sessions
                foreach ($blendedSessions as $session) {
                    foreach ($employees->take(5) as $employee) {
                        TrainingAttendance::create([
                            'session_id' => $session->id,
                            'employee_id' => $employee->id,
                            'notes' => null,
                            'recorded_at' => Carbon::now(),
                        ]);
                    }
                }
            }
        }
    }
}
