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

        // 1. Create Training
        $training = Training::create([
            'name' => 'Safety Induction',
            'type' => 'IN',
            'group_comp' => 'BMC',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => "in_progress",
        ]);

        // 2. Data Trainer
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

        // 3. Create Sessions (one per day in the training date range)
        $sessions = [];
        $dayNumber = 1;
        foreach (CarbonPeriod::create($training->start_date, $training->end_date) as $date) {
            $sessions[] = TrainingSession::create([
                'training_id' => $training->id,
                'trainer_id' => $trainer->id,
                'room_name' => 'Wakatobi',
                'date' => $date->toDateString(),
                'room_location' => "EDC {$dayNumber}",
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'day_number' => $dayNumber,
                'status' => 'in_progress',
            ]);
            $dayNumber++;
        }

        $employees = User::inRandomOrder()
            ->limit(10)
            ->get();

        foreach ($employees as $employee) {
            TrainingAssessment::create(attributes: [
                'training_id' => $training->id,
                'employee_id' => $employee->id,
            ]);
        }

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
