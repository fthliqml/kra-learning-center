<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\LearningModule;
use App\Models\Training;
use App\Models\User;
use App\Models\UserCourse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $training = Training::query()->firstOrCreate(
            ['name' => 'Onboarding Training'],
            [
                'type' => 'IN',
                'group_comp' => 'BMC',
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'status' => 'in_progress',
            ]
        );

        // Pastikan ada minimal beberapa employee.
        if (User::where('role', 'employee')->count() === 0) {
            // Buat 5 sample employees
            for ($i = 1; $i <= 5; $i++) {
                User::query()->create([
                    'name' => "Employee Seed {$i}",
                    'email' => "employee{$i}@example.com",
                    'password' => 'password',
                    'section' => 'General',
                    'NRP' => 100100 + $i,
                    'role' => 'employee',
                ]);
            }
        }
        $employees = User::where('role', 'employee')->get();

        $data = [
            [
                'training_id' => $training->id,
                'title' => 'Safety Orientation',
                'description' => 'Fundamental safety practices and workplace hazard awareness.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Quality Basics',
                'description' => 'Introduction to quality control and basic defect identification.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Lean Fundamentals',
                'description' => 'Seven wastes, value stream mapping, and continuous improvement mindset.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'inactive',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Basic Web Development',
                'description' => 'Introduction to HTML, CSS, and JavaScript.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'inactive',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Effective Communication',
                'description' => 'Techniques and strategies for clear workplace communication.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Time Management',
                'description' => 'Principles and tools for managing time and increasing productivity.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Team Collaboration',
                'description' => 'Building teamwork skills and effective group problem solving.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'inactive',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Workplace Ethics',
                'description' => 'Understanding ethical behavior and integrity at work.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Problem Solving Skills',
                'description' => 'Structured approaches to identifying and solving workplace problems.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Customer Service Excellence',
                'description' => 'Best practices for delivering outstanding customer service.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'inactive',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Microsoft Excel Basics',
                'description' => 'Fundamentals of using Microsoft Excel for data management.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Conflict Resolution',
                'description' => 'Strategies for resolving conflicts in the workplace.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'inactive',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Presentation Skills',
                'description' => 'How to create and deliver effective presentations.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Introduction to Project Management',
                'description' => 'Basic concepts and tools for managing projects.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'inactive',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Emotional Intelligence',
                'description' => 'Understanding and improving emotional intelligence at work.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'active',
            ],
            [
                'training_id' => $training->id,
                'title' => 'Digital Literacy',
                'description' => 'Essential digital skills for the modern workplace.',
                'thumbnail_url' => 'images/courses/javascript.jpg',
                'status' => 'inactive',
            ],
        ];

        $createdCourses = [];
        foreach (array_values($data) as $index => $item) {
            // 3. Course idempotent: berdasarkan title + training.
            $course = Course::query()->firstOrCreate(
                ['training_id' => $item['training_id'], 'title' => $item['title']],
                [
                    'description' => $item['description'],
                    'thumbnail_url' => $item['thumbnail_url'],
                    'status' => $item['status'],
                ]
            );

            $existing = $course->learningModules()->count();
            $targetModules = max($existing, 6);
            if ($existing < $targetModules) {
                for ($i = $existing + 1; $i <= $targetModules; $i++) {
                    $type = $i % 2 === 0 ? 'pdf' : 'video';
                    LearningModule::create([
                        'course_id' => $course->id,
                        'title' => "Module {$i} - {$course->title}",
                        'description' => 'Seeded learning module for demo & progress.',
                        'content_type' => $type,
                        'url' => $type === 'video' ? 'https://example.com/video.mp4' : 'https://example.com/doc.pdf',
                        'is_completed' => false,
                    ]);
                }
            }

            $createdCourses[] = $course;
        }

        // ===== Assignment fase 2: per-employee pilih subset course =====
        $totalCourses = count($createdCourses);
        if ($totalCourses === 0 || $employees->count() === 0) {
            return; // nothing to assign
        }

        foreach ($employees as $empIndex => $employee) {
            // Tentukan target jumlah course per employee (misal 50-60% dari total)
            $min = (int) max(1, floor($totalCourses * 0.5));
            $max = (int) max($min, ceil($totalCourses * 0.6));
            // Gunakan hash deterministik agar konsisten antar seed ulang
            $seed = crc32('emp-' . $employee->id . '-courses');
            $range = $max - $min;
            $countForEmployee = $min + ($range > 0 ? ($seed % ($range + 1)) : 0);

            // Shuffle deterministik: sort by hash value
            $shuffled = collect($createdCourses)->sortBy(function ($c) use ($employee) {
                return crc32($employee->id . '-' . $c->id);
            })->values();

            $selected = $shuffled->take($countForEmployee);

            foreach ($selected as $course) {
                $total = max(1, $course->learningModules()->count());
                $progressSeed = crc32('p-' . $employee->id . '-' . $course->id);
                $ratio = (($progressSeed % 75) + 10) / 100; // 10% - 84%
                $currentStep = (int) max(1, floor($total * $ratio));
                $status = 'in_progress';
                // 1 dari 12 kemungkinan jadi completed jika ratio > 70%
                if ($currentStep >= $total - 1 && ($progressSeed % 12) === 0) {
                    $currentStep = $total;
                    $status = 'completed';
                } elseif ($currentStep >= $total) {
                    $currentStep = $total - 1;
                }

                UserCourse::updateOrCreate([
                    'user_id' => $employee->id,
                    'course_id' => $course->id,
                ], [
                    'current_step' => $currentStep,
                    'status' => $status,
                ]);
            }
        }
    }
}
