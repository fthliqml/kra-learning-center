<?php

namespace Database\Seeders;

use App\Models\InstructorDailyRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InstructorDailyRecordSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get users with instructor role
    $instructors = User::whereHas('userRoles', fn($q) => $q->where('role', 'instructor'))->get();

    if ($instructors->isEmpty()) {
      $this->command->warn('No instructors found. Skipping InstructorDailyRecordSeeder.');
      return;
    }

    $activities = [
      '101' => 'Formal Teaching',
      '102' => 'Non Formal Teaching',
      '201' => 'Formal Development',
      '202' => 'Non Formal Development',
      '301' => 'Develop Training Aid',
      '302' => 'Prepare/Report',
      '303' => 'Observasi & Konsultasi Teknis',
      '304' => 'Project/Job Assignment',
      '305' => 'Meeting',
      '306' => 'Travel',
      '400' => 'Others',
    ];

    $remarks = [
      'Excellent participation from attendees',
      'Need follow-up session next week',
      'All participants passed the assessment',
      'Materials need to be updated',
      'Great feedback from participants',
      'Rescheduled from previous date',
      'Completed ahead of schedule',
      'Some equipment issues encountered',
      'Successfully completed all objectives',
      'Additional resources requested',
      null, // Allow null remarks
    ];

    // Generate records for the past 30 days
    $startDate = Carbon::now()->subDays(30);
    $endDate = Carbon::now();

    foreach ($instructors as $instructor) {
      // Generate 15-25 records per instructor
      $recordCount = rand(15, 25);

      for ($i = 0; $i < $recordCount; $i++) {
        $date = Carbon::createFromTimestamp(
          rand($startDate->timestamp, $endDate->timestamp)
        )->startOfDay();

        // Skip weekends occasionally
        if ($date->isWeekend() && rand(1, 10) > 3) {
          continue;
        }

        $code = array_rand($activities);
        $activity = $activities[$code];
        $remark = $remarks[array_rand($remarks)];
        $hour = rand(1, 100) <= 80
          ? rand(6, 8) + (rand(0, 1) * 0.5) // 80% chance: 6-8.5 hours
          : rand(2, 5) + (rand(0, 1) * 0.5); // 20% chance: 2-5.5 hours

        InstructorDailyRecord::create([
          'instructor_id' => $instructor->id,
          'date' => $date->format('Y-m-d'),
          'code' => $code,
          'activity' => $activity,
          'remarks' => $remark,
          'hour' => $hour,
        ]);
      }
    }
  }
}
