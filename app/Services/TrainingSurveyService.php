<?php

namespace App\Services;

use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplateDefault;
use App\Models\Training;
use App\Models\TrainingSurvey;
use Illuminate\Support\Facades\DB;

class TrainingSurveyService
{
  /**
   * Create Level 1 survey when training is closed.
   * Auto-populate questions from default template based on group_comp.
   */
  public function createSurveyForTraining(Training $training): ?TrainingSurvey
  {
    // Get group_comp from training's competency
    $groupComp = $this->getGroupCompForTraining($training);

    // Get default template for this group_comp
    $defaultTemplate = SurveyTemplateDefault::getDefaultTemplate($groupComp, 1);

    // Check if survey already exists
    $existingSurvey = TrainingSurvey::where('training_id', $training->id)
      ->where('level', 1)
      ->first();

    if ($existingSurvey) {
      // If survey exists but is draft with no questions, populate from template
      if (
        $existingSurvey->status === TrainingSurvey::STATUS_DRAFT
        && $existingSurvey->questions()->count() === 0
        && $defaultTemplate
      ) {
        return DB::transaction(function () use ($existingSurvey, $defaultTemplate) {
          $this->copyQuestionsFromTemplate($existingSurvey, $defaultTemplate);

          // Update status if questions were added
          $questionCount = $existingSurvey->questions()->count();
          if ($questionCount >= 3) {
            $existingSurvey->status = TrainingSurvey::STATUS_INCOMPLETE;
            $existingSurvey->save();
          }

          return $existingSurvey;
        });
      }
      return $existingSurvey;
    }

    return DB::transaction(function () use ($training, $defaultTemplate) {
      // Create the survey with appropriate status
      // If template has questions >= 3, status = incomplete (ready for employees)
      // If no template/questions, status = draft (admin needs to add questions)
      $hasEnoughQuestions = $defaultTemplate && $defaultTemplate->questions()->count() >= 3;

      $survey = TrainingSurvey::create([
        'training_id' => $training->id,
        'level' => 1,
        'status' => $hasEnoughQuestions ? TrainingSurvey::STATUS_INCOMPLETE : TrainingSurvey::STATUS_DRAFT,
      ]);

      // If there's a default template, copy its questions
      if ($defaultTemplate) {
        $this->copyQuestionsFromTemplate($survey, $defaultTemplate);
      }

      return $survey;
    });
  }

  /**
   * Get group_comp from training (via competency relationship).
   */
  protected function getGroupCompForTraining(Training $training): ?string
  {
    // Try direct competency
    if ($training->competency_id && $training->competency) {
      return $training->competency->type;
    }

    // Try via module
    if ($training->module_id && $training->module?->competency) {
      return $training->module->competency->type;
    }

    // Try via course
    if ($training->course_id && $training->course?->competency) {
      return $training->course->competency->type;
    }

    return null;
  }

  /**
   * Copy questions from template to survey.
   */
  protected function copyQuestionsFromTemplate(TrainingSurvey $survey, $template): void
  {
    // Load template questions with options
    $templateQuestions = $template->questions()
      ->with('options')
      ->orderBy('order')
      ->get();

    foreach ($templateQuestions as $templateQuestion) {
      // Create survey question
      $question = SurveyQuestion::create([
        'training_survey_id' => $survey->id,
        'text' => $templateQuestion->text,
        'question_type' => $templateQuestion->question_type,
        'order' => $templateQuestion->order,
      ]);

      // Copy options if any
      foreach ($templateQuestion->options as $templateOption) {
        SurveyOption::create([
          'question_id' => $question->id,
          'text' => $templateOption->text,
          'order' => $templateOption->order ?? 0,
        ]);
      }
    }
  }
}
