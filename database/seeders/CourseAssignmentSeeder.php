<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\UserCourse;
use Illuminate\Support\Facades\DB;

class CourseAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $courses = Course::all();

        if ($users->isEmpty() || $courses->isEmpty()) {
            $this->command?->warn('No users or courses found. Skipping CourseAssignmentSeeder.');
            return;
        }

        // Configurable bounds: each user gets a random number of courses between MIN and MAX (clamped to available)
        $MIN_PER_USER = 3;  // adjust as needed
        $MAX_PER_USER = 7;  // adjust as needed

        if ($MIN_PER_USER > $MAX_PER_USER) {
            [$MIN_PER_USER, $MAX_PER_USER] = [$MAX_PER_USER, $MIN_PER_USER];
        }

        $now = now();
        $totalInserted = 0;

        foreach ($users as $user) {
            // Decide how many this user should get (cannot exceed total courses)
            $targetCount = random_int($MIN_PER_USER, $MAX_PER_USER);
            $targetCount = min($targetCount, $courses->count());

            // Fetch already assigned course IDs to avoid duplicates
            $already = UserCourse::where('user_id', $user->id)->pluck('course_id')->all();
            $remainingCourses = $courses->whereNotIn('id', $already);

            if ($remainingCourses->isEmpty()) {
                continue; // user already has all courses
            }

            // Random subset for this user (if remaining < target, will pick all remaining)
            $picked = $remainingCourses->shuffle()->take($targetCount);

            $inserts = [];
            foreach ($picked as $course) {
                $inserts[] = [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'current_step' => 0,
                    'status' => 'not_started',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($inserts) {
                DB::table('user_courses')->insert($inserts);
                $totalInserted += count($inserts);
                $this->command?->line("User {$user->id}: assigned " . count($inserts) . ' courses');
            }
        }

        if ($totalInserted) {
            $this->command?->info('Total inserted user-course assignments: ' . $totalInserted);
        } else {
            $this->command?->line('No new user-course assignments inserted.');
        }

        // Ensure employee@example.com has some courses for demo
        $employeeUser = User::where('email', 'employee@example.com')->first();
        if ($employeeUser) {
            $assignedCourses = UserCourse::where('user_id', $employeeUser->id)->pluck('course_id')->toArray();
            $availableCourses = Course::whereNotIn('id', $assignedCourses)->get();
            $toAssign = $availableCourses->take(5); // assign up to 5 courses
            foreach ($toAssign as $course) {
                UserCourse::create([
                    'user_id' => $employeeUser->id,
                    'course_id' => $course->id,
                    'current_step' => 0,
                    'status' => 'not_started',
                ]);
                $totalInserted++;
            }
        }
    }
}
