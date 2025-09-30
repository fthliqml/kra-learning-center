<?php

namespace App\Livewire\Components\Courses;

use Illuminate\Support\Str;
use Livewire\Component;

class PretestQuestions extends Component
{
    public array $questions = [];

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

    public function render()
    {
        return view('components.courses.pretest-questions');
    }
}
