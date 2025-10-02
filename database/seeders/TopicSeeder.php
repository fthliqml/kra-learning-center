<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopicSeeder extends Seeder
{
    public int $minPerCourse = 2;
    public int $maxPerCourse = 4;

    public function run(): void
    {
        $courses = Course::query()->get();
        foreach ($courses as $course) {
            // Skip if course already has topics
            if ($course->topics()->exists()) {
                continue;
            }
            $count = random_int($this->minPerCourse, $this->maxPerCourse);
            for ($i = 1; $i <= $count; $i++) {
                $title = $this->generateTitle($course->title, $i);
                Topic::create([
                    'course_id' => $course->id,
                    'title' => $title,
                ]);
            }
        }
    }

    protected function generateTitle(string $courseTitle, int $index): string
    {
        $base = [
            'Introduction',
            'Fundamentals',
            'Core Concepts',
            'Advanced Topics',
            'Practical Application',
            'Case Studies',
            'Summary',
        ];
        $picked = $base[$index - 1] ?? ('Module ' . $index);
        return $picked . ' - ' . Str::headline(Str::limit($courseTitle, 30, ''));
    }
}
