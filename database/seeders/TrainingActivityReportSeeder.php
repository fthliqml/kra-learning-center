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
use Illuminate\Database\Seeder;

/**
 * Seeds 5 approved trainings for Training Activity Report feature.
 * 
 * Creates realistic data with:
 * - 1 IN-HOUSE, 1 OUT-HOUSE, 1 LMS, 2 BLENDED trainings
 * - Small participant count (3-5 per training)
 * - Realistic attendance percentages (min 75% to pass)
 * - Varied scores based on attendance
 */
class TrainingActivityReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required signers
        $sectionHead = User::where('position', 'section_head')
            ->whereRaw('LOWER(section) = ?', ['lid'])
            ->first();
        
        $deptHead = User::where('position', 'department_head')->first();

        if (!$sectionHead || !$deptHead) {
            $sectionHead = User::where('email', 'admin@example.com')->first();
            $deptHead = $sectionHead;
        }

        // Get competencies
        $competencies = Competency::all();
        if ($competencies->isEmpty()) {
            $this->command->warn('No competencies found. Skipping.');
            return;
        }

        // Get trainers
        $trainers = Trainer::all();
        if ($trainers->isEmpty()) {
            $this->command->warn('No trainers found. Please run TrainingSeeder first.');
            return;
        }

        // Get training modules and courses
        $trainingModules = TrainingModule::with('competency')->get();
        $courses = \App\Models\Course::with('competency')->get();

        // Get employees for participants (small pool)
        $allEmployees = User::where('position', 'employee')->inRandomOrder()->limit(12)->get();
        if ($allEmployees->count() < 5) {
            $allEmployees = User::inRandomOrder()->limit(8)->get();
        }

        // 5 trainings config: 1 IN, 1 OUT, 1 LMS, 2 BLENDED
        $trainingConfigs = [
            [
                'name' => 'Basic Safety Orientation',
                'type' => 'IN',
                'duration' => 2,
                'daysAgo' => 15, // ~2 weeks ago (Newest)
                'participantCount' => 4,
            ],
            [
                'name' => 'Advanced Excel for Business',
                'type' => 'OUT',
                'duration' => 3,
                'daysAgo' => 72, // ~2.5 months ago
                'participantCount' => 3,
            ],
            [
                'name' => 'Information Security Fundamentals',
                'type' => 'LMS',
                'duration' => 1,
                'daysAgo' => 55, // ~2 months ago
                'participantCount' => 5,
            ],
            [
                'name' => 'Project Management Essentials',
                'type' => 'BLENDED',
                'duration' => 4,
                'daysAgo' => 95, // ~3 months ago (Oldest)
                'participantCount' => 4,
            ],
            [
                'name' => 'Data Analysis with Python',
                'type' => 'BLENDED',
                'duration' => 3,
                'daysAgo' => 30, // ~1 month ago
                'participantCount' => 3,
            ],
        ];

        // Room options
        $rooms = ['Wakatobi', 'Komodo', 'Bunaken', 'Raja Ampat'];
        $locations = ['EDC Lt.1', 'EDC Lt.2', 'Gedung Training'];

        $employeeIndex = 0;

        foreach ($trainingConfigs as $config) {
            // Create training dates
            $start = Carbon::now()->subDays($config['daysAgo'] + rand(-5, 5));
            $end = $start->copy()->addDays($config['duration'] - 1);

            // Approval dates (realistic delay after training)
            $sectionHeadSignedAt = $end->copy()->addDays(rand(3, 7));
            $deptHeadSignedAt = $sectionHeadSignedAt->copy()->addDays(rand(1, 4));

            $competency = $competencies->random();

            $trainingData = [
                'name' => $config['name'],
                'type' => $config['type'],
                'competency_id' => $competency->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'approved',
                'section_head_signed_by' => $sectionHead->id,
                'section_head_signed_at' => $sectionHeadSignedAt,
                'dept_head_signed_by' => $deptHead->id,
                'dept_head_signed_at' => $deptHeadSignedAt,
            ];

            // Assign module/course based on type
            if ($config['type'] === 'IN' && $trainingModules->isNotEmpty()) {
                $trainingData['module_id'] = $trainingModules->random()->id;
            } elseif (in_array($config['type'], ['LMS', 'BLENDED']) && $courses->isNotEmpty()) {
                $trainingData['course_id'] = $courses->random()->id;
            }

            $training = Training::create($trainingData);

            // Create sessions (not for LMS - purely online)
            $sessions = [];
            if ($config['type'] !== 'LMS') {
                $dayNumber = 1;
                foreach (CarbonPeriod::create($training->start_date, $training->end_date) as $date) {
                    $trainer = $trainers->random();
                    
                    // Randomize time slots
                    $startHour = rand(8, 10);
                    $endHour = $startHour + rand(3, 6);
                    
                    $sessions[] = TrainingSession::create([
                        'training_id' => $training->id,
                        'trainer_id' => $trainer->id,
                        'room_name' => $rooms[array_rand($rooms)],
                        'room_location' => $locations[array_rand($locations)],
                        'date' => $date->toDateString(),
                        'start_time' => sprintf('%02d:00:00', $startHour),
                        'end_time' => sprintf('%02d:00:00', $endHour),
                        'day_number' => $dayNumber,
                        'status' => 'done',
                    ]);
                    $dayNumber++;
                }
            }

            $totalSessions = count($sessions);

            // Get participants (rotate through employees, not always same people)
            $participants = $allEmployees->slice($employeeIndex, $config['participantCount']);
            if ($participants->count() < $config['participantCount']) {
                // Wrap around
                $remaining = $config['participantCount'] - $participants->count();
                $participants = $participants->merge($allEmployees->take($remaining));
            }
            $employeeIndex = ($employeeIndex + $config['participantCount']) % $allEmployees->count();

            foreach ($participants as $pIndex => $participant) {
                // Generate realistic attendance pattern
                // Some people have perfect attendance, some miss 1-2 sessions
                $attendancePattern = $this->generateAttendancePattern($totalSessions, $pIndex);
                $presentCount = array_sum($attendancePattern);
                $attendancePercentage = $totalSessions > 0 
                    ? round(($presentCount / $totalSessions) * 100) 
                    : 100; // LMS = 100%

                // Determine pass/fail based on attendance (min 75%)
                $hasMinAttendance = $attendancePercentage >= 75;
                
                // Generate scores based on attendance and random performance
                $scores = $this->generateRealisticScores($attendancePercentage, $hasMinAttendance);

                // Determine final status
                $isPassed = $hasMinAttendance && $scores['posttest'] >= 70;

                // Create assessment with attendance percentage
                TrainingAssessment::create([
                    'training_id' => $training->id,
                    'employee_id' => $participant->id,
                    'pretest_score' => $scores['pretest'],
                    'posttest_score' => $scores['posttest'],
                    'practical_score' => $scores['practical'],
                    'attendance_percentage' => $attendancePercentage,
                    'status' => $isPassed ? 'passed' : 'failed',
                ]);

                // Create attendance records
                foreach ($sessions as $sIndex => $session) {
                    $isPresent = $attendancePattern[$sIndex] ?? true;
                    
                    TrainingAttendance::create([
                        'session_id' => $session->id,
                        'employee_id' => $participant->id,
                        'status' => $isPresent ? 'present' : 'absent',
                        'notes' => $isPresent ? null : $this->getAbsenceReason(),
                        'recorded_at' => Carbon::parse($session->date)->setTime(8, rand(0, 30)),
                    ]);
                }
            }

            $this->command->info("✓ {$config['type']}: {$config['name']} ({$config['participantCount']} participants)");
        }

        $this->command->info('');
        $this->command->info('✅ Created 5 approved trainings for Training Activity Report');
    }

    /**
     * Generate realistic attendance pattern
     */
    private function generateAttendancePattern(int $totalSessions, int $participantIndex): array
    {
        if ($totalSessions === 0) {
            return [];
        }

        $pattern = array_fill(0, $totalSessions, true);

        // Different patterns for different participants
        switch ($participantIndex % 4) {
            case 0:
                // Perfect attendance
                break;
            case 1:
                // Miss 1 random session if > 2 days
                if ($totalSessions > 2) {
                    $pattern[rand(0, $totalSessions - 1)] = false;
                }
                break;
            case 2:
                // Miss first or last session
                if ($totalSessions > 1) {
                    $pattern[rand(0, 1) === 0 ? 0 : $totalSessions - 1] = false;
                }
                break;
            case 3:
                // ~80% attendance
                $absences = max(1, (int) floor($totalSessions * 0.2));
                for ($i = 0; $i < $absences && $i < $totalSessions; $i++) {
                    $pattern[rand(0, $totalSessions - 1)] = false;
                }
                break;
        }

        return $pattern;
    }

    /**
     * Generate realistic scores based on attendance
     */
    private function generateRealisticScores(int $attendancePercentage, bool $hasMinAttendance): array
    {
        if (!$hasMinAttendance) {
            // Low attendance = lower scores
            return [
                'pretest' => rand(40, 60),
                'posttest' => rand(50, 68),
                'practical' => rand(45, 65),
            ];
        }

        // Good attendance correlates with better scores
        $baseBonus = ($attendancePercentage - 75) / 25 * 10; // 0-10 bonus

        return [
            'pretest' => rand(55, 75),
            'posttest' => min(100, rand(70, 85) + (int) $baseBonus),
            'practical' => min(100, rand(68, 82) + (int) $baseBonus),
        ];
    }

    /**
     * Get random absence reason
     */
    private function getAbsenceReason(): string
    {
        $reasons = [
            'Sick leave',
            'Family emergency',
            'Prior work commitment',
            'Travel delay',
            'Personal matter',
        ];

        return $reasons[array_rand($reasons)];
    }
}
