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
use Illuminate\Database\Seeder;

class TrainingHistorySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get employee@example.com user
    $employee = User::where('email', 'employee@example.com')->first();

    if (!$employee) {
      $this->command->warn('employee@example.com not found. Skipping TrainingHistorySeeder.');
      return;
    }

    // Get or create trainers
    $trainers = Trainer::all();
    if ($trainers->isEmpty()) {
      $trainerUser = User::whereHas('userRoles', fn($q) => $q->where('role', 'instructor'))->first() ?? User::first();
      $trainers = collect([
        Trainer::create([
          'user_id' => $trainerUser->id,
          'name' => 'John Instructor',
          'institution' => 'KRA'
        ])
      ]);
    }

    $trainingTypes = ['IN', 'OUT', 'LMS'];
    $groupComps = ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC'];
    $trainingNames = [
      'Spring Boot Fundamentals',
      'Laravel Advanced Techniques',
      'React JS Essentials',
      'Docker & Kubernetes',
      'AWS Cloud Architecture',
      'Database Design & Optimization',
      'Cybersecurity Best Practices',
      'Agile & Scrum Methodology',
      'Python Data Science',
      'DevOps Engineering',
      'Mobile App Development',
      'REST API Design',
      'Microservices Architecture',
      'Machine Learning Basics',
      'Git Version Control',
      'CI/CD Pipeline Setup',
      'Node.js Backend Development',
      'Vue.js Framework',
      'Software Testing Strategies',
      'Leadership & Management'
    ];

    // Create 20 completed trainings (15 passed + 5 failed)
    for ($i = 0; $i < 20; $i++) {
      $isPassed = $i < 15; // First 15 are passed, last 5 are failed

      // Random dates in the past (last 6 months)
      $daysAgo = rand(30, 180);
      $start = Carbon::now()->subDays($daysAgo);
      $duration = rand(2, 5); // 2-5 days training
      $end = $start->copy()->addDays($duration - 1);

      $training = Training::create([
        'name' => $trainingNames[$i],
        'type' => $trainingTypes[array_rand($trainingTypes)],
        'group_comp' => $groupComps[array_rand($groupComps)],
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
        'status' => 'done', // Important: must be 'done' to show in history
      ]);

      // Create training sessions
      $sessions = [];
      $dayNumber = 1;
      foreach (CarbonPeriod::create($training->start_date, $training->end_date) as $date) {
        $trainer = $trainers->random();
        $sessions[] = TrainingSession::create([
          'training_id' => $training->id,
          'trainer_id' => $trainer->id,
          'room_name' => 'Room ' . chr(64 + rand(1, 5)), // A-E
          'room_location' => 'Floor ' . rand(1, 5),
          'date' => $date->toDateString(),
          'start_time' => '08:00:00',
          'end_time' => '16:00:00',
          'day_number' => $dayNumber,
          'status' => 'done',
        ]);
        $dayNumber++;
      }

      // Create attendance records for employee@example.com
      $totalSessions = count($sessions);
      $presentCount = $isPassed ? $totalSessions : rand(floor($totalSessions * 0.5), $totalSessions - 1);

      foreach ($sessions as $index => $session) {
        $isPresent = $index < $presentCount;
        TrainingAttendance::create([
          'session_id' => $session->id,
          'employee_id' => $employee->id,
          'status' => $isPresent ? 'present' : 'absent',
          'notes' => $isPresent ? null : 'Sick leave',
          'recorded_at' => Carbon::parse($session->date)->setTime(8, 0),
        ]);
      }

      // Create assessment for employee@example.com
      if ($isPassed) {
        // Passed: good scores
        TrainingAssessment::create([
          'training_id' => $training->id,
          'employee_id' => $employee->id,
          'pretest_score' => rand(75, 90),
          'posttest_score' => rand(80, 95),
          'practical_score' => rand(80, 95),
          'status' => 'passed',
        ]);
      } else {
        // Failed: low scores
        TrainingAssessment::create([
          'training_id' => $training->id,
          'employee_id' => $employee->id,
          'pretest_score' => rand(40, 60),
          'posttest_score' => rand(45, 65),
          'practical_score' => rand(40, 60),
          'status' => 'failed',
        ]);
      }

      // Add some random employees to these trainings too
      $randomEmployees = User::where('id', '!=', $employee->id)
        ->where('position', 'employee')
        ->inRandomOrder()
        ->limit(rand(3, 8))
        ->get();

      foreach ($randomEmployees as $randomEmp) {
        // Create attendances for random employees
        foreach ($sessions as $session) {
          TrainingAttendance::create([
            'session_id' => $session->id,
            'employee_id' => $randomEmp->id,
            'status' => rand(0, 10) < 8 ? 'present' : 'absent', // 80% present
            'notes' => null,
            'recorded_at' => Carbon::parse($session->date)->setTime(8, 0),
          ]);
        }

        // Create assessments for random employees
        TrainingAssessment::create([
          'training_id' => $training->id,
          'employee_id' => $randomEmp->id,
          'pretest_score' => rand(60, 90),
          'posttest_score' => rand(65, 95),
          'practical_score' => rand(60, 90),
          'status' => rand(0, 10) < 7 ? 'passed' : 'failed', // 70% passed
        ]);
      }
    }

    $this->command->info('Training history seeded successfully!');
    $this->command->info('- 15 passed trainings for employee@example.com');
    $this->command->info('- 5 failed trainings for employee@example.com');
    $this->command->info('- Random attendances for other employees');
  }
}
