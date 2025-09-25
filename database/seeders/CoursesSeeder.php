<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Training with ID=1 exists before running this seeder.
        // Create three sample courses bound to training_id = 1
        $data = [
            [
                'training_id' => 1,
                'title' => 'Introduction to Core Concepts',
                'description' => 'A foundational overview of the core concepts covered in the training program.',
                'thumbnail_url' => 'https://picsum.photos/seed/course1/800/400',
                'status' => 'active',
            ],
            [
                'training_id' => 1,
                'title' => 'Intermediate Practices and Patterns',
                'description' => 'Dive deeper into intermediate practices with practical patterns and examples.',
                'thumbnail_url' => 'https://picsum.photos/seed/course2/800/400',
                'status' => 'inactive',
            ],
            [
                'training_id' => 1,
                'title' => 'Advanced Topics and Case Studies',
                'description' => 'Explore advanced topics and real-world case studies to solidify understanding.',
                'thumbnail_url' => 'https://picsum.photos/seed/course3/800/400',
                'status' => 'active',
            ],
        ];

        foreach ($data as $item) {
            Course::create($item);
        }

    }
}
