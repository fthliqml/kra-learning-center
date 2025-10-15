<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SurveyTemplate;
use App\Models\SurveyQuestion;
use App\Models\SurveyOption;
use App\Models\Training;
use App\Models\TrainingSurvey;

class TrainingSurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create 2 survey templates
            $templates = [];
            $templates[] = SurveyTemplate::create([
                'name' => 'Post-Training Reaction (Level 1)',
                'description' => 'Kirkpatrick Level 1 - reaction feedback survey',
            ]);
            $templates[] = SurveyTemplate::create([
                'name' => 'Learning Outcomes (Level 2)',
                'description' => 'Kirkpatrick Level 2 - learning outcomes self-evaluation',
            ]);

            // Seed questions and options for each template
            foreach ($templates as $ti => $template) {
                for ($i = 1; $i <= 3; $i++) {
                    $q = SurveyQuestion::create([
                        'template_id' => $template->id,
                        'text' => "Question {$i} for {$template->name}",
                        'order' => $i,
                    ]);
                    // 4 options (Likert scale-like)
                    $options = ['Strongly Disagree', 'Disagree', 'Agree', 'Strongly Agree'];
                    foreach ($options as $oi => $optText) {
                        SurveyOption::create([
                            'question_id' => $q->id,
                            'text' => $optText,
                            'order' => $oi + 1,
                        ]);
                    }
                }
            }

            // Pick 5 trainings to attach surveys to
            $trainingIds = Training::query()->inRandomOrder()->limit(5)->pluck('id');
            // If not enough trainings, create 5 simple placeholders
            if ($trainingIds->count() < 5) {
                $toCreate = 5 - $trainingIds->count();
                for ($i = 1; $i <= $toCreate; $i++) {
                    $t = Training::create([
                        'name' => 'Seeded Training #' . ($trainingIds->count() + $i),
                        'type' => 'IN',
                        'group_comp' => 'BMC',
                        'start_date' => now()->toDateString(),
                        'end_date' => now()->toDateString(),
                    ]);
                    $trainingIds->push($t->id);
                }
                $trainingIds = $trainingIds->take(5);
            }

            // Create 5 surveys distributed across the two templates
            $levels = [1, 1, 2, 2, 1];
            $statuses = [
                TrainingSurvey::STATUS_DRAFT,
                TrainingSurvey::STATUS_INCOMPLETE,
                TrainingSurvey::STATUS_DRAFT,
                TrainingSurvey::STATUS_COMPLETED,
                TrainingSurvey::STATUS_INCOMPLETE,
            ];

            foreach ($trainingIds->values() as $idx => $trainingId) {
                TrainingSurvey::create([
                    'training_id' => $trainingId,
                    'template_id' => $templates[$idx % 2]->id,
                    'level' => $levels[$idx] ?? 1,
                    'status' => $statuses[$idx] ?? TrainingSurvey::STATUS_DRAFT,
                ]);
            }
        });
    }
}
