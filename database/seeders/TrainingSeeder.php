<?php

namespace Database\Seeders;

use App\Models\Training;
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

        for ($i = 1; $i <= 3; $i++) {
            $training_session = TrainingSession::create([
                'training_id' => $training->id,
                // 'instructor_id' => 1,
                'room_name' => 'Wakatobi',
                'room_location' => 'EDC',
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'day_number' => $i,
                'status' => 'in_progress',
            ]);
        }

        $employees = User::where('role', 'employee')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        foreach ($training_session as $session) {
            foreach ($employees as $employee) {
                TrainingAttendance::create([
                    'session_id' => $session->id,
                    'employee_id' => $employee->id,
                    'status' => 'present',
                    'notes' => null,
                    'recorded_at' => Carbon::now(),
                ]);
            }
        }
    }
}
