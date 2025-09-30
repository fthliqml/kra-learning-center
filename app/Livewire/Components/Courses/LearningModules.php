<?php

namespace App\Livewire\Components\Courses;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class LearningModules extends Component
{
    use WithFileUploads;

    public array $topics = [];

    public function mount(): void
    {
        if (empty($this->topics)) {
            $this->topics = [
                [
                    'id' => Str::uuid()->toString(),
                    'title' => '',
                    'resources' => [],
                    'quiz' => [
                        'enabled' => false,
                        'questions' => [],
                    ],
                ]
            ];
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

    /* Topics CRUD */
    public function addTopic(): void
    {
        $this->topics[] = [
            'id' => Str::uuid()->toString(),
            'title' => '',
            'resources' => [],
            'quiz' => ['enabled' => false, 'questions' => []],
        ];
    }

    public function removeTopic(int $index): void
    {
        if (isset($this->topics[$index])) {
            unset($this->topics[$index]);
            $this->topics = array_values($this->topics);
        }
    }

    /* Resources */
    public function addTopicResource(int $topicIndex, string $type): void
    {
        if (!isset($this->topics[$topicIndex]))
            return;
        if ($type === 'pdf') {
            $this->topics[$topicIndex]['resources'][] = ['type' => 'pdf', 'file' => null];
        } elseif ($type === 'youtube') {
            $this->topics[$topicIndex]['resources'][] = ['type' => 'youtube', 'url' => ''];
        }
    }

    public function removeTopicResource(int $topicIndex, int $resourceIndex): void
    {
        if (isset($this->topics[$topicIndex]['resources'][$resourceIndex])) {
            unset($this->topics[$topicIndex]['resources'][$resourceIndex]);
            $this->topics[$topicIndex]['resources'] = array_values($this->topics[$topicIndex]['resources']);
        }
    }

    /* Quiz */
    public function toggleTopicQuiz(int $topicIndex): void
    {
        if (!isset($this->topics[$topicIndex]))
            return;
        $enabled = (bool) ($this->topics[$topicIndex]['quiz']['enabled'] ?? false);
        $this->topics[$topicIndex]['quiz']['enabled'] = !$enabled;
        if ($this->topics[$topicIndex]['quiz']['enabled'] && empty($this->topics[$topicIndex]['quiz']['questions'])) {
            $this->addTopicQuizQuestion($topicIndex);
        }
    }

    public function addTopicQuizQuestion(int $topicIndex): void
    {
        if (!isset($this->topics[$topicIndex]))
            return;
        $this->topics[$topicIndex]['quiz']['questions'][] = $this->makeQuestion();
    }

    public function removeTopicQuizQuestion(int $topicIndex, int $questionIndex): void
    {
        if (isset($this->topics[$topicIndex]['quiz']['questions'][$questionIndex])) {
            unset($this->topics[$topicIndex]['quiz']['questions'][$questionIndex]);
            $this->topics[$topicIndex]['quiz']['questions'] = array_values($this->topics[$topicIndex]['quiz']['questions']);
        }
    }

    public function addTopicQuizOption(int $t, int $q): void
    {
        if (isset($this->topics[$t]['quiz']['questions'][$q]) && ($this->topics[$t]['quiz']['questions'][$q]['type'] ?? '') === 'multiple') {
            $this->topics[$t]['quiz']['questions'][$q]['options'][] = '';
        }
    }

    public function removeTopicQuizOption(int $t, int $q, int $o): void
    {
        if (isset($this->topics[$t]['quiz']['questions'][$q]['options'][$o])) {
            unset($this->topics[$t]['quiz']['questions'][$q]['options'][$o]);
            $this->topics[$t]['quiz']['questions'][$q]['options'] = array_values($this->topics[$t]['quiz']['questions'][$q]['options']);
        }
    }

    public function updated($prop): void
    {
        if (!is_array($prop) && preg_match('/^topics\.(\d+)\.quiz\.questions\.(\d+)\.type$/', $prop, $m)) {
            $t = (int) $m[1];
            $q = (int) $m[2];
            $type = $this->topics[$t]['quiz']['questions'][$q]['type'] ?? null;
            if ($type === 'essay') {
                $this->topics[$t]['quiz']['questions'][$q]['options'] = [];
            } elseif ($type === 'multiple' && empty($this->topics[$t]['quiz']['questions'][$q]['options'])) {
                $this->topics[$t]['quiz']['questions'][$q]['options'] = [''];
            }
        }
    }

    public function goNext(): void
    {
        $this->dispatch('setTab', 'post-test');
    }
    public function goBack(): void
    {
        $this->dispatch('setTab', 'pretest');
    }

    public function render()
    {
        return view('components.courses.learning-modules');
    }
}
