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
      'JAI' => [
        'Conducted safety training session for new employees',
        'Led practical welding workshop for production team',
        'Facilitated quality control training module',
        'Delivered leadership skills development program',
        'Conducted equipment operation training',
        'Led problem-solving workshop session',
        'Facilitated team building activities',
        'Conducted technical skills assessment',
        'Delivered customer service excellence training',
        'Led process improvement workshop',
      ],
      'JAO' => [
        'Attended external training on ISO 9001 standards',
        'Participated in industry conference on safety practices',
        'Completed online certification course',
        'Attended vendor training on new equipment',
        'Participated in regional training summit',
        'Completed advanced instructor certification',
        'Attended workshop on modern teaching methods',
        'Participated in cross-company knowledge sharing',
        'Completed safety officer refresher course',
        'Attended leadership development seminar',
      ],
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

        $group = rand(1, 100) <= 70 ? 'JAI' : 'JAO'; // 70% JAI, 30% JAO
        $activity = $activities[$group][array_rand($activities[$group])];
        $remark = $remarks[array_rand($remarks)];
        $hour = rand(1, 100) <= 80
          ? rand(6, 8) + (rand(0, 1) * 0.5) // 80% chance: 6-8.5 hours
          : rand(2, 5) + (rand(0, 1) * 0.5); // 20% chance: 2-5.5 hours

        InstructorDailyRecord::create([
          'instructor_id' => $instructor->id,
          'date' => $date->format('Y-m-d'),
          'group' => $group,
          'activity' => $activity,
          'remarks' => $remark,
          'hour' => $hour,
        ]);
      }
    }

    $this->command->info('Instructor Daily Record seeding completed successfully!');
  }
}
