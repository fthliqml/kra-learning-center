<?php

namespace App\Livewire\Components\SurveyTemplate;

use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\SurveyQuestionsValidator;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\SurveyTemplateOption;

class EditTemplateQuestions extends Component
{
  use Toast;

  public int $surveyLevel = 1;
  public int $surveyId = 0;

  public array $questions = [];
  public array $errorQuestionIndexes = [];

  public function mount(int $surveyLevel, int $surveyId)
  {
    $this->surveyLevel = $surveyLevel;
    $this->surveyId = $surveyId;
    $this->hydrateFromSurvey();
    if (empty($this->questions)) {
      $this->questions = [$this->makeQuestion()];
    }
  }

  protected function hydrateFromSurvey(): void
  {
    $template = SurveyTemplate::with(['questions.options'])->find($this->surveyId);
    if (!$template) {
      $this->questions = [];
      return;
    }
    $loaded = [];
    foreach ($template->questions->sortBy('order')->values() as $qModel) {
      $options = $qModel->options->sortBy('order')->values();
      $opts = $options->map(fn($o) => $o->text)->all();
      $loaded[] = [
        'id' => (string) $qModel->id,
        'question_type' => $qModel->question_type ?? 'multiple',
        'text' => $qModel->text ?? '',
        'options' => ($qModel->question_type === 'multiple') ? $opts : [],
      ];
    }
    $this->questions = $loaded;
  }

  public function makeQuestion(string $type = 'multiple'): array
  {
    return [
      'id' => Str::uuid()->toString(),
      'question_type' => $type,
      'text' => '',
      'options' => $type === 'multiple' ? [''] : [],
    ];
  }

  public function addQuestion(): void
  {
    $this->questions[] = $this->makeQuestion();
  }

  public function removeQuestion(int $index): void
  {
    if (isset($this->questions[$index])) {
      unset($this->questions[$index]);
      $this->questions = array_values($this->questions);
    }
  }

  public function addOption(int $qIndex): void
  {
    if (isset($this->questions[$qIndex]) && $this->questions[$qIndex]['question_type'] === 'multiple') {
      $this->questions[$qIndex]['options'][] = '';
    }
  }

  public function removeOption(int $qIndex, int $oIndex): void
  {
    if (isset($this->questions[$qIndex]['options'][$oIndex])) {
      unset($this->questions[$qIndex]['options'][$oIndex]);
      $this->questions[$qIndex]['options'] = array_values($this->questions[$qIndex]['options']);
    }
  }

  public function reorderByIds(array $orderedIds): void
  {
    if (empty($orderedIds)) {
      return;
    }
    $current = array_map(fn($q) => $q['id'], $this->questions);
    if ($current === $orderedIds) {
      return;
    }
    $lookup = [];
    foreach ($this->questions as $q) {
      $lookup[$q['id']] = $q;
    }
    $new = [];
    foreach ($orderedIds as $id) {
      if (isset($lookup[$id])) {
        $item = $lookup[$id];
        if ($item['question_type'] === 'essay') {
          $item['options'] = [];
        } elseif ($item['question_type'] === 'multiple' && empty($item['options'])) {
          $item['options'] = [''];
        }
        $new[] = $item;
        unset($lookup[$id]);
      }
    }
    foreach ($lookup as $left) {
      if ($left['question_type'] === 'essay') {
        $left['options'] = [];
      } elseif ($left['question_type'] === 'multiple' && empty($left['options'])) {
        $left['options'] = [''];
      }
      $new[] = $left;
    }
    $this->questions = $new;
  }

  public function saveDraft(): void
  {
    $this->errorQuestionIndexes = [];

    $validator = new SurveyQuestionsValidator();
    $result = $validator->validate($this->questions);
    $errors = $result['errors'];
    $this->errorQuestionIndexes = $result['errorQuestionIndexes'];

    if (!empty($errors)) {
      $bulletLines = collect($errors)->take(6)->map(fn($e) => '• ' . $e);
      $display = $bulletLines->implode("\n");
      if (count($errors) > 6) {
        $display .= "\n..." . (count($errors) - 6) . " more errors";
      }
      $htmlMessage = "<div style=\"white-space:pre-line; text-align:left\"><strong>Validation failed:</strong>\n" . e($display) . '</div>';
      $this->error($htmlMessage, timeout: 10000, position: 'toast-top toast-center');
      return;
    }

    if (empty($this->questions)) {
      $this->error('Cannot save empty survey template. Add at least 3 questions.', timeout: 6000, position: 'toast-top toast-center');
      return;
    }

    foreach ($this->questions as &$q) {
      $q['text'] = trim($q['text'] ?? '');
      if (($q['question_type'] ?? '') === 'multiple') {
        $q['options'] = array_map(fn($o) => trim($o), $q['options'] ?? []);
      } else {
        $q['options'] = [];
      }
    }
    unset($q);

    DB::transaction(function () {
      $template = SurveyTemplate::find($this->surveyId);
      if (!$template) {
        $this->error('Survey template not found.', timeout: 6000, position: 'toast-top toast-center');
        return;
      }

      SurveyTemplateOption::whereHas('question', fn($q) => $q->where('survey_template_id', $template->id))->delete();
      $template->questions()->delete();

      foreach ($this->questions as $qOrder => $q) {
        $questionModel = SurveyTemplateQuestion::create([
          'survey_template_id' => $template->id,
          'question_type' => in_array($q['question_type'] ?? '', ['multiple', 'essay']) ? $q['question_type'] : 'multiple',
          'text' => $q['text'] ?: 'Untitled Question',
          'order' => $qOrder,
        ]);

        if (($q['question_type'] ?? '') === 'multiple') {
          foreach (($q['options'] ?? []) as $optIndex => $optText) {
            $optText = trim($optText);
            if ($optText === '')
              continue;
            SurveyTemplateOption::create([
              'survey_template_question_id' => $questionModel->id,
              'text' => $optText,
              'order' => $optIndex,
            ]);
          }
        }
      }

      // Status rules: >=3 => active, otherwise draft
      if (count($this->questions) >= 3) {
        if ($template->status !== 'active') {
          $template->status = 'active';
          $template->save();
        }
      } else {
        if ($template->status !== 'draft') {
          $template->status = 'draft';
          $template->save();
        }
      }
    });

    $this->errorQuestionIndexes = [];
    $this->success('Survey template questions saved successfully', timeout: 4000, position: 'toast-top toast-center');
  }

  public function render()
  {
    return view('components.survey-template.edit-template-questions');
  }

  public function placeholder()
  {
    return view('components.skeletons.edit-survey-form-skeleton');
  }
}
