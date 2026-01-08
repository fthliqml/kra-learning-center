<?php

namespace App\Livewire\Pages\dashboard;

use App\Models\Certification;
use App\Models\Training;
use App\Models\Trainer;
use App\Models\TestAttempt;
use Livewire\Component;

class InstructorDashboard extends Component
{
    // Calendar events
    public array $calendarEvents = [];

    // Upcoming schedules (trainings + certifications)
    public array $upcomingSchedules = [];
    public int $totalUpcomingCount = 0;

    // Trainings needing test review
    public array $trainingsNeedReview = [];

    public function mount()
    {
        $this->loadCalendarEvents();
        $this->loadUpcomingSchedules();
        $this->loadTrainingsNeedReview();
    }

    public function loadCalendarEvents()
    {
        $this->calendarEvents = [];

        // Get trainings for current month and next 2 months
        $startDate = now()->startOfMonth();
        $endDate = now()->addMonths(2)->endOfMonth();

        $userId = auth()->id();

        // Get trainer record for current user
        $trainer = \App\Models\Trainer::where('user_id', $userId)->first();

        // Instructor sees only trainings where they are the trainer
        if ($trainer) {
            $trainings = Training::with(['sessions.trainer.user'])
                ->whereBetween('start_date', [$startDate, $endDate])
                ->whereHas('sessions', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })
                ->get();

            foreach ($trainings as $training) {
                $dateKey = $training->start_date->format('Y-m-d');

                if (!isset($this->calendarEvents[$dateKey])) {
                    $this->calendarEvents[$dateKey] = [];
                }

                // Get first session details
                $firstSession = $training->sessions->first();
                $trainerName = $firstSession?->trainer?->user?->name ?? 'TBA';
                $location = $firstSession?->room_location ?? 'TBA';
                $time = $firstSession ? ($firstSession->start_time ? substr($firstSession->start_time, 0, 5) : 'TBA') . ' - ' . ($firstSession->end_time ? substr($firstSession->end_time, 0, 5) : 'TBA') : 'TBA';

                $this->calendarEvents[$dateKey][] = [
                    'title' => $training->name ?? 'Training',
                    'type' => $training->status === 'pending' ? 'warning' : 'normal',
                    'category' => 'training',
                    'trainer' => $trainerName,
                    'location' => $location,
                    'time' => $time,
                ];
            }
        }

        // Instructor sees only certifications where they are participant
        $certifications = Certification::with(['sessions'])
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('employee_id', $userId);
            })
            ->whereHas('sessions', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->get();

        foreach ($certifications as $certification) {
            // Add each session as separate calendar event
            foreach ($certification->sessions as $session) {
                if ($session->date < $startDate || $session->date > $endDate) {
                    continue;
                }

                $dateKey = $session->date->format('Y-m-d');

                if (!isset($this->calendarEvents[$dateKey])) {
                    $this->calendarEvents[$dateKey] = [];
                }

                $sessionTypeLabel = match ($session->type) {
                    'theory' => 'Theory',
                    'practical' => 'Practical',
                    default => ucfirst($session->type ?? ''),
                };

                $time = $session->start_time
                    ? substr($session->start_time, 0, 5) . ' - ' . ($session->end_time ? substr($session->end_time, 0, 5) : 'TBA')
                    : 'TBA';

                $this->calendarEvents[$dateKey][] = [
                    'title' => ($certification->name ?? 'Certification') . ' - ' . $sessionTypeLabel,
                    'type' => 'certification', // orange color
                    'category' => 'certification',
                    'trainer' => $sessionTypeLabel . ' Session',
                    'location' => $session->location ?? 'TBA',
                    'time' => $time,
                ];
            }
        }
    }

    public function loadUpcomingSchedules()
    {
        $this->upcomingSchedules = [];
        $userId = auth()->id();
        $trainer = \App\Models\Trainer::where('user_id', $userId)->first();

        // Get upcoming trainings where instructor is trainer
        if ($trainer) {
            $trainings = Training::with(['sessions.trainer.user'])
                ->where('start_date', '>=', now()->startOfDay())
                ->whereHas('sessions', function ($q) use ($trainer) {
                    $q->where('trainer_id', $trainer->id);
                })
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();

            foreach ($trainings as $training) {
                $firstSession = $training->sessions->first();
                $location = $firstSession?->room_location ?? null;
                $time = null;

                if ($firstSession && $firstSession->start_time) {
                    $time = substr($firstSession->start_time, 0, 5);
                    if ($firstSession->end_time) {
                        $time .= ' - ' . substr($firstSession->end_time, 0, 5);
                    }
                }

                $this->upcomingSchedules[] = [
                    'id' => $training->id,
                    'name' => $training->name ?? 'Training',
                    'start_date' => $training->start_date->format('Y-m-d'),
                    'end_date' => $training->end_date ? $training->end_date->format('Y-m-d') : null,
                    'location' => $location,
                    'time' => $time,
                    'type' => $training->type,
                    'status' => $training->status,
                    'schedule_type' => 'training', // training or certification
                ];
            }
        }

        // Get upcoming certifications where user is participant
        // Each session (theory/practical) shown as separate item
        $certifications = Certification::with(['sessions'])
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('employee_id', $userId);
            })
            ->whereHas('sessions', function ($q) {
                $q->where('date', '>=', now()->startOfDay());
            })
            ->get();

        foreach ($certifications as $certification) {
            // Get all upcoming sessions for this certification
            $upcomingSessions = $certification->sessions
                ->where('date', '>=', now()->startOfDay())
                ->sortBy('date');

            // Add each session as separate schedule item
            foreach ($upcomingSessions as $session) {
                $location = $session->location ?? null;
                $time = null;

                if ($session->start_time) {
                    $time = substr($session->start_time, 0, 5);
                    if ($session->end_time) {
                        $time .= ' - ' . substr($session->end_time, 0, 5);
                    }
                }

                // Session type label (Theory/Practical)
                $sessionTypeLabel = match ($session->type) {
                    'theory' => 'Theory',
                    'practical' => 'Practical',
                    default => ucfirst($session->type ?? ''),
                };

                $this->upcomingSchedules[] = [
                    'id' => $certification->id,
                    'name' => $certification->name ?? 'Certification',
                    'start_date' => $session->date->format('Y-m-d'),
                    'end_date' => null, // No range, single session
                    'location' => $location,
                    'time' => $time,
                    'type' => $session->type ?? null,
                    'session_type_label' => $sessionTypeLabel,
                    'status' => $certification->status,
                    'schedule_type' => 'certification', // training or certification
                ];
            }
        }

        // Sort all schedules by start_date
        usort($this->upcomingSchedules, function ($a, $b) {
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        // Store total count before limiting
        $this->totalUpcomingCount = count($this->upcomingSchedules);

        // Limit to 5 for display
        $this->upcomingSchedules = array_slice($this->upcomingSchedules, 0, 5);
    }

    /**
     * Load trainings that need test review
     * Handles both IN (module-based) and LMS (course-based) trainings
     */
    public function loadTrainingsNeedReview()
    {
        $this->trainingsNeedReview = [];
        $userId = auth()->id();
        $trainer = Trainer::where('user_id', $userId)->first();

        if (!$trainer) {
            return;
        }

        // Get IN type trainings with module tests that need review
        $inTrainings = Training::with(['module.pretest', 'module.posttest'])
            ->where('type', 'IN')
            ->whereHas('sessions', fn($s) => $s->where('trainer_id', $trainer->id))
            ->whereHas('module', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('pretest.attempts', fn($a) => $a->where('status', TestAttempt::STATUS_UNDER_REVIEW))
                        ->orWhereHas('posttest.attempts', fn($a) => $a->where('status', TestAttempt::STATUS_UNDER_REVIEW));
                });
            })
            ->limit(5)
            ->get();

        foreach ($inTrainings as $training) {
            $module = $training->module;
            $pretest = $module?->pretest;
            $posttest = $module?->posttest;

            $testIds = collect([$pretest?->id, $posttest?->id])->filter()->values()->all();

            $needReviewCount = 0;
            if (!empty($testIds)) {
                $needReviewCount = TestAttempt::whereIn('test_id', $testIds)
                    ->where('status', TestAttempt::STATUS_UNDER_REVIEW)
                    ->count();
            }

            if ($needReviewCount > 0) {
                $this->trainingsNeedReview[] = [
                    'id' => $training->id,
                    'name' => $training->name,
                    'type' => $training->type,
                    'need_review_count' => $needReviewCount,
                    'has_pretest' => $pretest !== null,
                    'has_posttest' => $posttest !== null,
                ];
            }
        }

        // Get LMS type trainings with course tests that need review
        $lmsTrainings = Training::with(['course.tests'])
            ->where('type', 'LMS')
            ->whereHas('course.tests', function ($q) {
                $q->whereHas('attempts', fn($a) => $a->where('status', TestAttempt::STATUS_UNDER_REVIEW));
            })
            ->limit(5)
            ->get();

        foreach ($lmsTrainings as $training) {
            $course = $training->course;
            $tests = $course?->tests ?? collect();
            $pretest = $tests->firstWhere('type', 'pretest');
            $posttest = $tests->firstWhere('type', 'posttest');

            $testIds = $tests->pluck('id')->filter()->values()->all();

            $needReviewCount = 0;
            if (!empty($testIds)) {
                $needReviewCount = TestAttempt::whereIn('test_id', $testIds)
                    ->where('status', TestAttempt::STATUS_UNDER_REVIEW)
                    ->count();
            }

            if ($needReviewCount > 0) {
                $this->trainingsNeedReview[] = [
                    'id' => $training->id,
                    'name' => $training->name,
                    'type' => $training->type,
                    'need_review_count' => $needReviewCount,
                    'has_pretest' => $pretest !== null,
                    'has_posttest' => $posttest !== null,
                ];
            }
        }

        // Sort by need_review_count descending and limit to 5
        usort($this->trainingsNeedReview, fn($a, $b) => $b['need_review_count'] - $a['need_review_count']);
        $this->trainingsNeedReview = array_slice($this->trainingsNeedReview, 0, 5);
    }

    public function render()
    {
        return view('pages.dashboard.instructor-dashboard');
    }
}
