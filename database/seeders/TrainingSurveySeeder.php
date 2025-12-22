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
use App\Models\SurveyResponse;
use App\Models\User;

class TrainingSurveySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    DB::transaction(function () {

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

      // Create 5 surveys distributed across level 1 and 3 only
      $levels = [1, 3, 1, 3, 1];
      $statuses = [
        TrainingSurvey::STATUS_DRAFT,
        TrainingSurvey::STATUS_INCOMPLETE,
        TrainingSurvey::STATUS_DRAFT,
        TrainingSurvey::STATUS_COMPLETED,
        TrainingSurvey::STATUS_INCOMPLETE,
      ];

      foreach ($trainingIds->values() as $idx => $trainingId) {
        $survey = TrainingSurvey::create([
          'training_id' => $trainingId,
          'level' => $levels[$idx] ?? 1,
          'status' => $statuses[$idx] ?? TrainingSurvey::STATUS_DRAFT,
        ]);

        // Create 3 questions for each survey
        for ($i = 1; $i <= 3; $i++) {
          $q = SurveyQuestion::create([
            'training_survey_id' => $survey->id,
            'text' => "Question {$i} for Survey {$survey->id}",
            'question_type' => 'multiple',
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

        // Assign survey responses to all users with position employee (or at least 1 demo user)
        $users = User::where('position', 'employee')->get();
        if ($users->isEmpty()) {
          // fallback: assign to demo user
          $users = User::where('email', 'employee@example.com')->get();
        }
        foreach ($users as $user) {
          SurveyResponse::create([
            'survey_id' => $survey->id,
            'employee_id' => $user->id,
            'is_completed' => false,
            'submitted_at' => null,
          ]);
        }
      }
    });
  }
}
