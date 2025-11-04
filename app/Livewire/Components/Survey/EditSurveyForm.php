<?php

namespace App\Livewire\Components\Survey;

use App\Services\SurveyQuestionsValidator;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingSurvey;
use App\Models\SurveyQuestion;
use App\Models\SurveyOption;
use App\Models\SurveyTemplate;

class EditSurveyForm extends Component
{
    use Toast;
    public $surveyLevel = 1;
    public ?int $selectedTemplateId = null;

    public function importFromTemplate($templateId = null)
    {
        $templateId = $templateId ?? $this->selectedTemplateId;
        if (!$templateId) {
            $this->error('Please select a template first.', timeout: 6000, position: 'toast-top toast-center');
            return;
        }

        $template = SurveyTemplate::with(['questions.options'])
            ->where('status', 'active')
            ->where('level', $this->surveyLevel)
            ->find($templateId);
        if (!$template) {
            $this->error('Template not found or not active.', timeout: 6000, position: 'toast-top toast-center');
            return;
        }
        $imported = [];
        foreach ($template->questions->sortBy('order')->values() as $qModel) {
            $options = $qModel->options->sortBy('order')->values();
            $opts = $options->map(fn($o) => $o->text)->all();
            $imported[] = [
                'id' => Str::uuid()->toString(),
                'question_type' => $qModel->question_type ?? 'multiple',
                'text' => $qModel->text ?? '',
                'options' => ($qModel->question_type === 'multiple') ? $opts : [],
            ];
        }
        $this->questions = array_merge($this->questions, $imported);
        $this->success('Template questions imported and appended successfully.', timeout: 4000, position: 'toast-top toast-center');
        // Close modal on UI
        if (method_exists($this, 'dispatch')) {
            // Livewire v3
            $this->dispatch('close-import-template-modal');
        } else {
            // Livewire v2 fallback
            $this->dispatchBrowserEvent('close-import-template-modal');
        }
    }
    public $surveyId = 1;

    public $questions = [];

    // Error highlighting: indexes of invalid questions
    public array $errorQuestionIndexes = [];

    protected function hydrateFromSurvey(): void
    {
        $survey = TrainingSurvey::with(['questions.options'])->find($this->surveyId);
        if (!$survey) {
            $this->questions = [];
            return;
        }
        $loaded = [];
        foreach ($survey->questions->sortBy('order')->values() as $qModel) {
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

    #[On('clearQuestions')]
    public function clearQuestions(): void
    {
        // User confirmed: clear all questions
        $this->questions = [];
        $this->errorQuestionIndexes = [];
        $this->success('All questions cleared.', timeout: 3000, position: 'toast-top toast-center');
        // notify confirm dialog to close and stop processing state
        $this->dispatch('confirm-done');
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
                'Cannot save empty survey. Add at least one question.',
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
            // Load the survey
            $survey = TrainingSurvey::find($this->surveyId);
            if (!$survey) {
                $this->error('Survey not found.', timeout: 6000, position: 'toast-top toast-center');
                return;
            }

            // Wipe existing options first to avoid foreign key constraint
            SurveyOption::whereHas('question', fn($q) => $q->where('training_survey_id', $survey->id))->delete();

            // Wipe existing questions (cascade deletes options if set, but we did it manually)
            $survey->questions()->delete();

            foreach ($this->questions as $qOrder => $q) {
                $questionModel = SurveyQuestion::create([
                    'training_survey_id' => $survey->id,
                    'question_type' => in_array($q['question_type'] ?? '', ['multiple', 'essay']) ? $q['question_type'] : 'multiple',
                    'text' => $q['text'] ?: 'Untitled Question',
                    'order' => $qOrder,
                ]);

                if (($q['question_type'] ?? '') === 'multiple') {
                    foreach (($q['options'] ?? []) as $optIndex => $optText) {
                        $optText = trim($optText);
                        if ($optText === '')
                            continue; // skip empty placeholder
                        SurveyOption::create([
                            'question_id' => $questionModel->id,
                            'text' => $optText,
                            'order' => $optIndex,
                        ]);
                    }
                }
            }

            // If questions >= 3, set survey status to incomplete
            if (count($this->questions) >= 3 && $survey->status !== TrainingSurvey::STATUS_INCOMPLETE) {
                $survey->status = TrainingSurvey::STATUS_INCOMPLETE;
                $survey->save();
            }
        });

        $this->errorQuestionIndexes = []; // clear highlights on success
        $this->success(
            'Survey questions saved successfully',
            timeout: 4000,
            position: 'toast-top toast-center'
        );
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
        $templateOptions = SurveyTemplate::select('id', 'title')
            ->where('level', $this->surveyLevel)
            ->where('status', 'active')
            ->orderBy('title')
            ->get();

        return view('components.survey.edit-survey-form', [
            'templateOptions' => $templateOptions,
        ]);
    }

    public function placeholder()
    {
        return view('components.skeletons.edit-survey-form-skeleton');
    }
}
