<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public int $minPerTopic = 2;
    public int $maxPerTopic = 5;

    public function run(): void
    {
        $topics = Topic::query()->withCount('sections')->get();
        foreach ($topics as $topic) {
            if ($topic->sections_count > 0) {
                continue; // don't duplicate
            }
            $count = random_int($this->minPerTopic, $this->maxPerTopic);
            for ($i = 1; $i <= $count; $i++) {
                Section::create([
                    'topic_id' => $topic->id,
                    'title' => $this->generateTitle($i),
                    'is_quiz_on' => $i === $count && random_int(0, 1) === 1, // sometimes enable quiz on last section
                ]);
            }
        }
    }

    protected function generateTitle(int $index): string
    {
        $names = [
            'Overview',
            'Deep Dive',
            'Hands On',
            'Examples',
            'Review',
            'Checkpoint',
            'Extensions',
        ];
        return $names[$index - 1] ?? ('Section ' . $index);
    }
}
