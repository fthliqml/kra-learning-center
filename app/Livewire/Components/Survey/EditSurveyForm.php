<?php

namespace App\Livewire\Components\Survey;

use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingSurvey;
use App\Models\SurveyQuestion;
use App\Models\SurveyOption;

class EditSurveyForm extends Component
{
    use Toast;
    public $surveyLevel = 1;
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
        $validator = new \App\Services\SurveyQuestionsValidator();
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
        return view('components.survey.edit-survey-form', [
        ]);
    }

    public function placeholder()
    {
        return view('components.skeletons.edit-survey-form-skeleton');
    }
}
