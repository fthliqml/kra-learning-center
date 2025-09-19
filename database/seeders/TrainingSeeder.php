<?php

namespace Database\Seeders;

use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAssesment;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
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
            'user_id' => 5,
            'institution' => "KRA"

        ]);

        Trainer::create([
            'user_id' => 10,
            'institution' => "KRA"

        ]);

        $sessions = [];
        for ($i = 1; $i <= 4; $i++) {
            $sessions[] = TrainingSession::create([
                'training_id' => $training->id,
                'trainer_id' => $trainer->id,
                'room_name' => 'Wakatobi',
                'room_location' => "EDC {$i}",
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'day_number' => $i,
                'status' => 'in_progress',
            ]);
        }

        $employees = User::inRandomOrder()
            ->limit(5)
            ->get();

        foreach ($employees as $employee) {
            TrainingAssesment::create(attributes: [
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
