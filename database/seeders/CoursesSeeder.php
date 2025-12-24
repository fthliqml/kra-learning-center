<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoursesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Pastikan ada minimal beberapa employee.
    if (User::where('position', 'employee')->count() === 0) {
      // Buat 5 sample employees
      for ($i = 1; $i <= 5; $i++) {
        User::query()->create([
          'name' => "Employee Seed {$i}",
          'email' => "employee{$i}@example.com",
          'password' => 'password',
          'section' => 'General',
          'nrp' => 100100 + $i,
          'position' => 'employee',
        ]);
      }
    }

    // Get competencies by type for mapping courses
    $competencies = Competency::all()->groupBy('type');

    // Helper to get random competency from a type
    $getCompetency = function ($type) use ($competencies) {
      if (!isset($competencies[$type]) || $competencies[$type]->isEmpty()) {
        return null;
      }
      return $competencies[$type]->random()->id;
    };

    // Course data with competency type mapping
    $data = [
      // BMC - Basic Management Competency courses
      ['title' => 'Safety Orientation', 'description' => 'Fundamental safety practices and workplace hazard awareness.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BMC'],
      ['title' => 'Quality Basics', 'description' => 'Introduction to quality control and basic defect identification.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BMC'],
      ['title' => 'Lean Fundamentals', 'description' => 'Seven wastes, value stream mapping, and continuous improvement mindset.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BMC'],
      ['title' => 'Problem Solving Skills', 'description' => 'Structured approaches to identifying and solving workplace problems.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BMC'],

      // BC - Behavioral Competency courses
      ['title' => 'Effective Communication', 'description' => 'Techniques and strategies for clear workplace communication.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BC'],
      ['title' => 'Team Collaboration', 'description' => 'Building teamwork skills and effective group problem solving.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BC'],
      ['title' => 'Workplace Ethics', 'description' => 'Understanding ethical behavior and integrity at work.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BC'],
      ['title' => 'Emotional Intelligence', 'description' => 'Understanding and improving emotional intelligence at work.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BC'],
      ['title' => 'Conflict Resolution', 'description' => 'Strategies for resolving conflicts in the workplace.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'BC'],

      // MMP - Manufacturing Management Program courses
      ['title' => 'Time Management', 'description' => 'Principles and tools for managing time and increasing productivity.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'MMP'],
      ['title' => 'Advanced Reporting', 'description' => 'Advanced reporting techniques under development.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'MMP'],
      ['title' => 'Data Analysis Basics', 'description' => 'Introductory data analysis concepts.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'MMP'],
      ['title' => 'Operational Excellence', 'description' => 'Process optimization & operational frameworks.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'MMP'],

      // LC - Leadership Competency courses
      ['title' => 'Leadership Essentials', 'description' => 'Leadership behaviors and influence fundamentals.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'LC'],
      ['title' => 'Coaching for Performance', 'description' => 'Coaching conversations and feedback models.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'LC'],
      ['title' => 'Strategic Thinking', 'description' => 'Long-term strategic analysis foundations.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'LC'],
      ['title' => 'Introduction to Project Management', 'description' => 'Basic concepts and tools for managing projects.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'LC'],

      // TC - Technical Competency courses
      ['title' => 'Basic Web Development', 'description' => 'Introduction to HTML, CSS, and JavaScript.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'TC'],
      ['title' => 'Microsoft Excel Basics', 'description' => 'Fundamentals of using Microsoft Excel for data management.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'TC'],
      ['title' => 'Digital Literacy', 'description' => 'Essential digital skills for the modern workplace.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'TC'],
      ['title' => 'Presentation Skills', 'description' => 'How to create and deliver effective presentations.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'TC'],
      ['title' => 'Customer Service Excellence', 'description' => 'Best practices for delivering outstanding customer service.', 'thumbnail_url' => 'images/courses/javascript.jpg', 'competency_type' => 'TC'],
    ];

    $statuses = ['draft', 'inactive', 'assigned'];
    foreach ($data as $item) {
      $hash = crc32($item['title']);
      // Weighted selection: more chance for 'assigned'
      $index = $hash % 100; // 0-99
      if ($index < 20) { // 20%
        $status = 'draft';
      } elseif ($index < 45) { // next 25%
        $status = 'inactive';
      } else { // remaining 55%
        $status = 'assigned';
      }

      // Get competency_id based on type
      $competencyId = $getCompetency($item['competency_type']);

      Course::query()->updateOrCreate(
        ['title' => $item['title']],
        [
          'description' => $item['description'],
          'thumbnail_url' => $item['thumbnail_url'],
          'competency_id' => $competencyId,
          'status' => $status,
        ]
      );
    }
  }
}
