<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call(UserSeeder::class);
    $this->call(CompetencySeeder::class);
    $this->call(TrainingModuleSeeder::class);
    $this->call(TrainingSeeder::class);
    $this->call(TrainingHistorySeeder::class);
    $this->call(TrainingSurveySeeder::class);
    $this->call(CoursesSeeder::class);
    $this->call(TopicSeeder::class);
    $this->call(SectionSeeder::class);
    $this->call(ResourceSeeder::class);
    $this->call(SectionQuizSeeder::class);
    $this->call(CourseAssignmentSeeder::class);
    $this->call(SurveyTemplateSeeder::class);
    $this->call(TrainingRequestSeeder::class);
    $this->call(CertificationModuleSeeder::class);
    $this->call(CertificationSeeder::class);
    $this->call(InstructorDailyRecordSeeder::class);
    $this->call(CourseTestSeeder::class);
  }
}
