<?php

namespace Database\Seeders;

use App\Models\Competency;
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

        // Get admin@example.com user to also add training history
        $admin = User::where('email', 'admin@example.com')->first();

        // Get competencies for random assignment
        $competencies = Competency::all();
        if ($competencies->isEmpty()) {
            $this->command->warn('No competencies found. Skipping TrainingHistorySeeder.');
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
            'Leadership & Management',
            'Angular Framework Mastery',
            'MongoDB Database Management',
            'GraphQL API Development',
            'TypeScript Advanced Patterns',
            'Redis Caching Strategies',
            'Elasticsearch Full-Text Search',
            'Kafka Message Streaming',
            'PostgreSQL Performance Tuning',
            'TDD & BDD Testing',
            'System Design Principles',
            'Clean Code Architecture'
        ];

        // Create 31 completed trainings (23 passed + 8 failed)
        for ($i = 0; $i < 31; $i++) {
            $isPassed = $i < 23; // First 23 are passed, last 8 are failed

            // Random dates in the past (last 6 months)
            $daysAgo = rand(30, 180);
            $start = Carbon::now()->subDays($daysAgo);
            $duration = rand(2, 5); // 2-5 days training
            $end = $start->copy()->addDays($duration - 1);

            $training = Training::create([
                'name' => $trainingNames[$i],
                'type' => $trainingTypes[array_rand($trainingTypes)],
                'competency_id' => $competencies->random()->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'done', // Important: must be 'done' to show in history
            ]);

            // Create training sessions
            // Decide if this training has per-day variation (every 3rd training has different room/time per day)
            $hasPerDayVariation = ($i % 3 === 0);
            
            $sessions = [];
            $dayNumber = 1;
            
            // Base settings for uniform trainings
            $baseRoom = 'Room ' . chr(64 + rand(1, 5)); // A-E
            $baseLocation = 'Floor ' . rand(1, 5);
            $baseStartTime = '08:00:00';
            $baseEndTime = '16:00:00';
            
            // Variation options for per-day trainings
            $roomOptions = ['Room A', 'Room B', 'Room C', 'Meeting Hall', 'Lab 1', 'Lab 2'];
            $locationOptions = ['Floor 1', 'Floor 2', 'Floor 3', 'Building A', 'Building B'];
            $timeSlots = [
                ['start' => '08:00:00', 'end' => '16:00:00'],
                ['start' => '09:00:00', 'end' => '17:00:00'],
                ['start' => '10:00:00', 'end' => '18:00:00'],
                ['start' => '08:30:00', 'end' => '15:30:00'],
                ['start' => '09:30:00', 'end' => '16:30:00'],
            ];
            
            foreach (CarbonPeriod::create($training->start_date, $training->end_date) as $date) {
                $trainer = $trainers->random();
                
                if ($hasPerDayVariation) {
                    // Different room/time for each day
                    $timeSlot = $timeSlots[array_rand($timeSlots)];
                    $sessions[] = TrainingSession::create([
                        'training_id' => $training->id,
                        'trainer_id' => $trainer->id,
                        'room_name' => $roomOptions[array_rand($roomOptions)],
                        'room_location' => $locationOptions[array_rand($locationOptions)],
                        'date' => $date->toDateString(),
                        'start_time' => $timeSlot['start'],
                        'end_time' => $timeSlot['end'],
                        'day_number' => $dayNumber,
                        'status' => 'done',
                    ]);
                } else {
                    // Uniform room/time for all days
                    $sessions[] = TrainingSession::create([
                        'training_id' => $training->id,
                        'trainer_id' => $trainer->id,
                        'room_name' => $baseRoom,
                        'room_location' => $baseLocation,
                        'date' => $date->toDateString(),
                        'start_time' => $baseStartTime,
                        'end_time' => $baseEndTime,
                        'day_number' => $dayNumber,
                        'status' => 'done',
                    ]);
                }
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

            // Create attendance and assessment for admin@example.com if exists
            if ($admin) {
                foreach ($sessions as $session) {
                    TrainingAttendance::create([
                        'session_id' => $session->id,
                        'employee_id' => $admin->id,
                        'status' => 'present',
                        'notes' => null,
                        'recorded_at' => Carbon::parse($session->date)->setTime(8, 0),
                    ]);
                }

                // Admin always passed with good scores
                TrainingAssessment::create([
                    'training_id' => $training->id,
                    'employee_id' => $admin->id,
                    'pretest_score' => rand(85, 95),
                    'posttest_score' => rand(90, 100),
                    'practical_score' => rand(85, 95),
                    'status' => 'passed',
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
    }
}
