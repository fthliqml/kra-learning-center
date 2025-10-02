<?php

namespace App\Livewire\Components\EditCourse;

use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;

class PretestQuestions extends Component
{
    use Toast;
    public array $questions = [];
    // Simple save draft flags (no dirty tracking to avoid overhead with large arrays)
    public bool $hasEverSaved = false;
    public bool $persisted = false; // mimic persistence success
    // Error highlighting: indexes of invalid questions
    public array $errorQuestionIndexes = [];

    protected $listeners = [];

    public function mount(): void
    {
        if (empty($this->questions)) {
            $this->questions = [$this->makeQuestion()];
        }
    }

    private function makeQuestion(string $type = 'multiple'): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'type' => $type,
            'question' => '',
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
        if (isset($this->questions[$qIndex]) && $this->questions[$qIndex]['type'] === 'multiple') {
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

    public function updated($prop): void
    {
        if (!is_array($prop) && preg_match('/^questions\\.(\d+)\\.type$/', $prop, $m)) {
            $i = (int) $m[1];
            $type = $this->questions[$i]['type'] ?? null;
            if ($type === 'essay') {
                $this->questions[$i]['options'] = [];
            } elseif ($type === 'multiple' && empty($this->questions[$i]['options'])) {
                $this->questions[$i]['options'] = [''];
            }
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
                if ($item['type'] === 'essay') {
                    $item['options'] = [];
                } elseif ($item['type'] === 'multiple' && empty($item['options'])) {
                    $item['options'] = [''];
                }
                $new[] = $item;
                unset($lookup[$id]);
            }
        }
        foreach ($lookup as $left) {
            if ($left['type'] === 'essay') {
                $left['options'] = [];
            } elseif ($left['type'] === 'multiple' && empty($left['options'])) {
                $left['options'] = [''];
            }
            $new[] = $left;
        }
        $this->questions = $new;
    }

    public function goNext(): void
    {
        $this->dispatch('setTab', 'learning-module');
    }
    public function goBack(): void
    {
        $this->dispatch('setTab', 'course-info');
    }

    public function saveDraft(): void
    {
        // Reset previous errors
        $this->errorQuestionIndexes = [];

        // Validate structure via service (mirrors LearningModules style)
        $validator = new \App\Services\PretestQuestionsValidator();
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
                'Cannot save empty pretest. Add at least one question.',
                timeout: 6000,
                position: 'toast-top toast-center'
            );
            return;
        }

        // Trim question & option texts before persistence
        foreach ($this->questions as &$q) {
            $q['question'] = trim($q['question'] ?? '');
            if (($q['type'] ?? '') === 'multiple') {
                $q['options'] = array_map(fn($o) => trim($o), $q['options'] ?? []);
            } else {
                $q['options'] = []; // ensure essay has no options
            }
        }
        unset($q);

        // TODO: Persist to DB (no schema shown). For now we mimic success.
        $this->persisted = true;
        $this->hasEverSaved = true;
        $this->errorQuestionIndexes = []; // clear highlights on success
        $this->success(
            'Pretest questions saved successfully',
            timeout: 4000,
            position: 'toast-top toast-center'
        );
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="p-6 space-y-4 animate-pulse">
            <div class="flex items-center gap-3">
                <div class="h-6 w-6 rounded-full bg-primary/20"></div>
                <div class="h-5 w-56 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="space-y-3">
                <div class="h-4 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-10 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="space-y-3">
                <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-10 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="space-y-3">
                <div class="h-4 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-10 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="h-10 w-40 bg-gray-200 dark:bg-gray-700 rounded"></div>
        </div>
        HTML;
    }

    public function render()
    {
        return view('components.edit-course.pretest-questions');
    }
}
