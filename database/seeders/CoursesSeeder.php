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
        // Pastikan ada training dengan ID 1; jika belum ada, buat satu.
        $training = Training::first();
        if (!$training) {
            $training = Training::create([
                'name' => 'Onboarding Training',
                'type' => 'IN',
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'status' => 'in_progress',
            ]);
        }

        // Ambil satu user untuk assignment progress; buat jika belum ada
        $user = User::query()->first();
        if (!$user) {
            $user = User::factory()->create([
                'name' => 'Sample User',
                'email' => 'sample.user@example.com',
            ]);
        }

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

        foreach (array_values($data) as $index => $item) {
            $course = Course::create($item);

            // Pastikan course punya beberapa module untuk perhitungan progress
            $existing = $course->learningModules()->count();
            $targetModules = max($existing, random_int(4, 8));
            if ($existing < $targetModules) {
                for ($i = $existing + 1; $i <= $targetModules; $i++) {
                    LearningModule::create([
                        'course_id' => $course->id,
                        'title' => "Module {$i} of {$course->title}",
                        'description' => 'Auto-seeded module for demo progress.',
                        'content_type' => 'video',
                        'url' => '#',
                        'is_completed' => false,
                    ]);
                }
            }

            // Hitung progress & buat assignment untuk user
            $total = max(1, $course->learningModules()->count());
            if ($index === 0) {
                $currentStep = $total; // 100%
                $status = 'completed';
            } else {
                $currentStep = random_int(1, max(1, $total - 1));
                $status = 'in_progress';
            }

            UserCourse::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'current_step' => $currentStep,
                    'status' => $status,
                ]
            );
        }
    }
}
