<?php

namespace App\Livewire\Components\Courses;

use Illuminate\Support\Str;
use Livewire\Component;

class PostTestQuestions extends Component
{
    public array $questions = [];
    public bool $hasEverSaved = false;
    public bool $persisted = false; // mimic persistence success

    protected $listeners = [];

    public function mount(): void
    {
        if (empty($this->questions)) {
            $this->questions = [$this->makeQuestion()];
        }
    }

    private function makeQuestion(string $type = 'multiple'): array
    {
        return ['id' => Str::uuid()->toString(), 'type' => $type, 'question' => '', 'options' => $type === 'multiple' ? [''] : []];
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
        if (!is_array($prop) && preg_match('/^questions\.(\d+)\.type$/', $prop, $m)) {
            $i = (int) $m[1];
            $t = $this->questions[$i]['type'] ?? null;
            if ($t === 'essay') {
                $this->questions[$i]['options'] = [];
            } elseif ($t === 'multiple' && empty($this->questions[$i]['options'])) {
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
        $map = [];
        foreach ($this->questions as $q) {
            $map[$q['id']] = $q;
        }
        $new = [];
        foreach ($orderedIds as $id) {
            if (isset($map[$id])) {
                $item = $map[$id];
                // normalize by type safety
                if ($item['type'] === 'essay') {
                    $item['options'] = [];
                } elseif ($item['type'] === 'multiple' && empty($item['options'])) {
                    $item['options'] = [''];
                }
                $new[] = $item;
                unset($map[$id]);
            }
        }
        foreach ($map as $left) { // append any leftovers (shouldn't normally happen)
            if ($left['type'] === 'essay') {
                $left['options'] = [];
            } elseif ($left['type'] === 'multiple' && empty($left['options'])) {
                $left['options'] = [''];
            }
            $new[] = $left;
        }
        $this->questions = $new;
    }

    public function finish(): void
    {   // TODO: persist all sections
        // After save redirect or show success message
        session()->flash('saved', 'Course content saved (placeholder).');
    }

    public function saveDraft(): void
    {
        $this->persisted = true;
        $this->hasEverSaved = true;
        session()->flash('saved', 'Post test questions saved (draft).');
    }

    public function goBack(): void
    {
        $this->dispatch('setTab', 'learning-module');
    }

    public function render()
    {
        return view('components.courses.post-test-questions');
    }

    public function placeholder(): string
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
}
