<?php

namespace App\Livewire\Pages\SurveyTemplate;

use App\Services\SurveyQuestionsValidator;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\SurveyTemplateOption;

class EditSurveyTemplate extends Component
{
  use Toast;
  public $surveyLevel = 1;
  public $surveyId = 1;

  // UI state
  public string $activeTab = 'template-info';

  public $questions = [];

  // Error highlighting: indexes of invalid questions
  public array $errorQuestionIndexes = [];

  public $template = null;

  // Template info form fields
  public string $templateTitle = '';
  public ?string $templateDescription = '';
  public int|string $templateLevel = 1;

  protected function hydrateFromSurvey(): void
  {
    $this->template = SurveyTemplate::with(['questions.options'])->find($this->surveyId);
    if (!$this->template) {
      $this->questions = [];
      return;
    }
    // Sync form fields for Template Information
    $this->templateTitle = (string) ($this->template->title ?? '');
    $this->templateDescription = $this->template->description ?? '';
    $this->templateLevel = $this->template->level ?? $this->surveyLevel ?? 1;
    // Keep route-provided level in sync with actual template level
    $this->surveyLevel = (int) ($this->template->level ?? $this->surveyLevel ?? 1);
    $loaded = [];
    foreach ($this->template->questions->sortBy('order')->values() as $qModel) {
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

  public function makeQuestion(string $type = 'multiple')
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
      return; // no change
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
    // Reset previous errors
    $this->errorQuestionIndexes = [];

    // Validate structure via service
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
      $this->error(
        $htmlMessage,
        timeout: 10000,
        position: 'toast-top toast-center'
      );
      return;
    }

    // If all questions removed somehow (should be caught earlier) block save
    if (empty($this->questions)) {
      $this->error(
        'Cannot save empty survey template. Add at least 3 questions.',
        timeout: 6000,
        position: 'toast-top toast-center'
      );
      return;
    }

    // Trim question & option texts before persistence
    foreach ($this->questions as &$q) {
      $q['text'] = trim($q['text'] ?? '');
      if (($q['question_type'] ?? '') === 'multiple') {
        $q['options'] = array_map(fn($o) => trim($o), $q['options'] ?? []);
      } else {
        $q['options'] = []; // ensure essay has no options
      }
    }
    unset($q);

    DB::transaction(function () {
      // Load the template
      $template = SurveyTemplate::find($this->surveyId);
      if (!$template) {
        $this->error('Survey template not found.', timeout: 6000, position: 'toast-top toast-center');
        return;
      }

      // Wipe existing options first to avoid foreign key constraint
      SurveyTemplateOption::whereHas('question', fn($q) => $q->where('survey_template_id', $template->id))->delete();

      // Wipe existing questions (cascade deletes options if set, but we did it manually)
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
              continue; // skip empty placeholder
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

    $this->errorQuestionIndexes = []; // clear highlights on success
    $this->success(
      'Survey template questions saved successfully',
      timeout: 4000,
      position: 'toast-top toast-center'
    );
  }

  public function saveTemplateInfo(): void
  {
    $this->validate([
      'templateTitle' => 'required|string|max:255',
      'templateDescription' => 'nullable|string|max:2000',
      'templateLevel' => 'required|integer|in:1,3',
    ]);

    $previousLevel = (int) ($this->surveyLevel ?? $this->templateLevel ?? 1);

    $template = SurveyTemplate::find($this->surveyId);
    if (!$template) {
      $this->error('Survey template not found.', timeout: 6000, position: 'toast-top toast-center');
      return;
    }

    $template->title = trim($this->templateTitle);
    $template->description = $this->templateDescription !== null ? trim($this->templateDescription) : null;
    $template->level = (int) $this->templateLevel;
    $template->save();

    // Update local reference and header display
    $this->template = $template->fresh(['questions.options']);
    $this->surveyLevel = (int) $template->level;

    $this->success(
      'Template information saved successfully',
      timeout: 4000,
      position: 'toast-top toast-center'
    );

    // If level changed, navigate to canonical URL reflecting new level
    if ($previousLevel !== (int) $template->level) {
      $this->redirectRoute(
        'survey-template.edit',
        ['level' => (int) $template->level, 'surveyId' => $template->id],
        navigate: true
      );
    }
  }

  public function mount()
  {
    $this->hydrateFromSurvey();
    if (empty($this->questions)) {
      $this->questions = [$this->makeQuestion()];
    }
  }

  public function render()
  {
    return view('pages.survey-template.edit-survey-template', [
      'template' => $this->template,
    ]);
  }

  public function placeholder()
  {
    return view('components.skeletons.edit-survey-form-skeleton');
  }
}
