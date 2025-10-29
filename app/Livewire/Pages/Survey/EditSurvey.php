<?php

namespace App\Livewire\Pages\Survey;

use Illuminate\Support\Str;
use Livewire\Component;
use App\Models\TrainingSurvey;
use App\Models\SurveyQuestion;

class EditSurvey extends Component
{
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

    public function save()
    {
        dump($this->questions);
    }

    public function back()
    {
        return redirect()->route('survey-management.index', ['level' => $this->surveyLevel]);
    }

    public function mount($level, $surveyId)
    {
        $this->surveyLevel = (int) $level;
        $this->surveyId = (int) $surveyId;
        $this->hydrateFromSurvey();
        if (empty($this->questions)) {
            $this->questions = [$this->makeQuestion()];
        }
    }

    public function render()
    {
        return view('pages.survey.edit-survey', [
        ]);
    }
}
