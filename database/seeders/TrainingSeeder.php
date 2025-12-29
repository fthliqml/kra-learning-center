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

        // Get competencies by type for specific assignments
        $bmcCompetencies = $competencies->where('type', 'BMC');
        $tocCompetencies = $competencies->where('type', 'TOC');
        $mmpCompetencies = $competencies->where('type', 'MMP');

        // 1. Ensure trainers exist
        // First trainer: internal user (instructor@example.com) - specializes in BMC & TOC
        $instructorUser = User::where('email', 'instructor@example.com')->first();
        $trainer = Trainer::create([
            'user_id' => $instructorUser?->id,
            'institution' => 'KRA',
        ]);
        // Assign competencies to first trainer (BMC and TOC)
        $trainerCompetencyIds = $bmcCompetencies->pluck('id')
            ->merge($tocCompetencies->pluck('id'))
            ->toArray();
        $trainer->competencies()->attach($trainerCompetencyIds);

        // Second trainer: external (no user_id) - specializes in Safety (BMC)
        $trainer2 = Trainer::create([
            'user_id' => null,
            'name' => 'Ahmad Fauzi',
            'institution' => 'PT. Safety Training Indonesia',
        ]);
        $trainer2->competencies()->attach($bmcCompetencies->pluck('id')->toArray());

        // Third trainer: external (no user_id) - specializes in MMP
        $trainer3 = Trainer::create([
            'user_id' => null,
            'name' => 'Budi Santoso',
            'institution' => 'Lembaga Sertifikasi Profesi',
        ]);
        $trainer3->competencies()->attach($mmpCompetencies->pluck('id')->toArray());

        // 2. Employees pool for assessments/attendances
        $employees = User::inRandomOrder()->limit(10)->get();
        $employeeUser = User::where('email', 'employee@example.com')->first();
        if ($employeeUser && !$employees->contains('id', $employeeUser->id)) {
            $employees->pop(); // remove one to make space
            $employees->push($employeeUser);
        }

        // Get a default competency for fallback (first BMC competency - Safety Awareness)
        $defaultCompetency = $competencies->where('code', 'BMC003')->first()
            ?? $bmcCompetencies->first()
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
    }
}
