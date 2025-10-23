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
        User::factory()->count(10)->create();
        $this->call(UserSeeder::class);
        $this->call(TrainingModuleSeeder::class);
        $this->call(TrainingSeeder::class);
        $this->call(TrainingSurveySeeder::class);
        $this->call(CoursesSeeder::class);
        $this->call(TopicSeeder::class);
        $this->call(SectionSeeder::class);
        $this->call(ResourceSeeder::class);
        $this->call(CourseAssignmentSeeder::class);
        $this->call(SurveyTemplateSeeder::class);
    }
}

