<?php

namespace Database\Seeders;

use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
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
        // 1. Ensure a trainer exists (reuse for all trainings)
        $trainer = Trainer::create([
            'user_id' => 1,
            'institution' => "KRA"

        ]);

        Trainer::create([
            'user_id' => 10,
            'institution' => "KRA"

        ]);

        Trainer::create([
            'user_id' => 10,
            'institution' => "KRA"

        ]);

        // 2. Employees pool for assessments/attendances
        $employees = User::inRandomOrder()
            ->limit(10)
            ->get();

        // 3. Create 6 trainings with sessions, assessments, and attendances
        for ($i = 1; $i <= 6; $i++) {
            $start = Carbon::now()->addDays(($i - 1) * 7); // stagger by weeks
            $end = $start->copy()->addDays(3);

            $training = Training::create([
                'name' => 'Safety Induction #' . $i,
                'type' => 'IN',
                'group_comp' => 'BMC',
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
