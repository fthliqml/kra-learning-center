<?php

namespace App\Livewire\Pages\Courses;


use Livewire\Component;
use Livewire\WithFileUploads;

class EditCourse extends Component
{
    use WithFileUploads;

    // Tabs
    public string $activeTab = 'course-info';

    // Course Info state (local only)
    public array $course = [
        'title' => '',
        'about' => '',
        'group_comp' => '',
    ];

    // Group options
    public array $groupOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    // Thumbnail temp upload (not persisted yet)
    public $thumbnail;

    // Pretest questions (local only)
    // Each item: ['type' => 'multiple'|'essay', 'question' => string, 'options' => array]
    public array $questions = [
        [
            'type' => 'multiple',
            'question' => '',
            'options' => [''],
        ],
    ];

    // Post test questions (same schema as pretest)
    public array $postQuestions = [
        [
            'type' => 'multiple',
            'question' => '',
            'options' => [''],
        ],
    ];

    /**
     * Learning Module Topics (flexible)
     * Structure per topic:
     * [
     *   'title' => string,
     *   'resources' => [
     *        ['type' => 'pdf', 'file' => TemporaryUploadedFile|null],
     *        ['type' => 'youtube', 'url' => '']
     *   ],
     *   'quiz' => [
     *        'enabled' => bool,
     *        'questions' => [ // same schema as pretest questions
     *            ['type'=>'multiple','question'=>'','options'=>['']]
     *        ]
     *   ]
     * ]
     */
    public array $topics = [
        [
            'title' => '',
            'resources' => [],
            'quiz' => [
                'enabled' => false,
                'questions' => [],
            ],
        ],
    ];

    // Actions
    public function goNextTab(string $to): void
    {
        $this->activeTab = $to;
    }

    public function addQuestionRow(): void
    {
        $this->questions[] = [
            'type' => 'multiple',
            'question' => '',
            'options' => [''],
        ];
    }

    public function removeQuestionRow(int $index): void
    {
        if (isset($this->questions[$index])) {
            unset($this->questions[$index]);
            $this->questions = array_values($this->questions);
        }
    }

    public function addOptionRow(int $qIndex): void
    {
        if (isset($this->questions[$qIndex]) && ($this->questions[$qIndex]['type'] ?? '') === 'multiple') {
            $this->questions[$qIndex]['options'][] = '';
        }
    }

    public function removeOptionRow(int $qIndex, int $oIndex): void
    {
        if (isset($this->questions[$qIndex]['options'][$oIndex])) {
            unset($this->questions[$qIndex]['options'][$oIndex]);
            $this->questions[$qIndex]['options'] = array_values($this->questions[$qIndex]['options']);
        }
    }

    public function updated($property): void
    {
        // If question type changes, normalize options: clear for essay, ensure at least one for multiple
        if (!is_array($property) && preg_match('/^questions\.(\d+)\.type$/', $property, $m)) {
            $idx = (int) $m[1];
            $type = $this->questions[$idx]['type'] ?? null;
            if ($type === 'essay') {
                $this->questions[$idx]['options'] = [];
            } elseif ($type === 'multiple') {
                if (empty($this->questions[$idx]['options'])) {
                    $this->questions[$idx]['options'] = [''];
                }
            }
        }

        // Post test question type change
        if (!is_array($property) && preg_match('/^postQuestions\.(\d+)\.type$/', $property, $m)) {
            $idx = (int) $m[1];
            $type = $this->postQuestions[$idx]['type'] ?? null;
            if ($type === 'essay') {
                $this->postQuestions[$idx]['options'] = [];
            } elseif ($type === 'multiple') {
                if (empty($this->postQuestions[$idx]['options'])) {
                    $this->postQuestions[$idx]['options'] = [''];
                }
            }
        }

        // Topic quiz question type change
        if (!is_array($property) && preg_match('/^topics\.(\d+)\.quiz\.questions\.(\d+)\.type$/', $property, $m)) {
            $tIdx = (int) $m[1];
            $qIdx = (int) $m[2];
            $type = $this->topics[$tIdx]['quiz']['questions'][$qIdx]['type'] ?? null;
            if ($type === 'essay') {
                $this->topics[$tIdx]['quiz']['questions'][$qIdx]['options'] = [];
            } elseif ($type === 'multiple') {
                if (empty($this->topics[$tIdx]['quiz']['questions'][$qIdx]['options'])) {
                    $this->topics[$tIdx]['quiz']['questions'][$qIdx]['options'] = [''];
                }
            }
        }
    }

    /* ----------------------------- Topics CRUD ----------------------------- */
    public function addTopic(): void
    {
        $this->topics[] = [
            'title' => '',
            'resources' => [],
            'quiz' => [
                'enabled' => false,
                'questions' => [],
            ],
        ];
    }

    public function removeTopic(int $index): void
    {
        if (isset($this->topics[$index])) {
            unset($this->topics[$index]);
            $this->topics = array_values($this->topics);
        }
    }

    /* --------------------------- Topic Resources --------------------------- */
    public function addTopicResource(int $topicIndex, string $type): void
    {
        if (!isset($this->topics[$topicIndex])) {
            return;
        }
        if ($type === 'pdf') {
            $this->topics[$topicIndex]['resources'][] = [
                'type' => 'pdf',
                'file' => null,
            ];
        } elseif ($type === 'youtube') {
            $this->topics[$topicIndex]['resources'][] = [
                'type' => 'youtube',
                'url' => '',
            ];
        }
    }

    public function removeTopicResource(int $topicIndex, int $resourceIndex): void
    {
        if (isset($this->topics[$topicIndex]['resources'][$resourceIndex])) {
            unset($this->topics[$topicIndex]['resources'][$resourceIndex]);
            $this->topics[$topicIndex]['resources'] = array_values($this->topics[$topicIndex]['resources']);
        }
    }

    /* -------------------------- Topic Quiz Handling ------------------------ */
    public function toggleTopicQuiz(int $topicIndex): void
    {
        if (!isset($this->topics[$topicIndex])) {
            return;
        }
        $enabled = (bool) ($this->topics[$topicIndex]['quiz']['enabled'] ?? false);
        $this->topics[$topicIndex]['quiz']['enabled'] = !$enabled;
        if ($this->topics[$topicIndex]['quiz']['enabled'] && empty($this->topics[$topicIndex]['quiz']['questions'])) {
            $this->addTopicQuizQuestion($topicIndex);
        }
    }

    public function addTopicQuizQuestion(int $topicIndex): void
    {
        if (!isset($this->topics[$topicIndex])) {
            return;
        }
        $this->topics[$topicIndex]['quiz']['questions'][] = [
            'type' => 'multiple',
            'question' => '',
            'options' => [''],
        ];
    }

    public function removeTopicQuizQuestion(int $topicIndex, int $questionIndex): void
    {
        if (isset($this->topics[$topicIndex]['quiz']['questions'][$questionIndex])) {
            unset($this->topics[$topicIndex]['quiz']['questions'][$questionIndex]);
            $this->topics[$topicIndex]['quiz']['questions'] = array_values($this->topics[$topicIndex]['quiz']['questions']);
        }
    }

    public function addTopicQuizOption(int $topicIndex, int $questionIndex): void
    {
        if (
            isset($this->topics[$topicIndex]['quiz']['questions'][$questionIndex]) &&
            ($this->topics[$topicIndex]['quiz']['questions'][$questionIndex]['type'] ?? '') === 'multiple'
        ) {
            $this->topics[$topicIndex]['quiz']['questions'][$questionIndex]['options'][] = '';
        }
    }

    public function removeTopicQuizOption(int $topicIndex, int $questionIndex, int $optionIndex): void
    {
        if (isset($this->topics[$topicIndex]['quiz']['questions'][$questionIndex]['options'][$optionIndex])) {
            unset($this->topics[$topicIndex]['quiz']['questions'][$questionIndex]['options'][$optionIndex]);
            $this->topics[$topicIndex]['quiz']['questions'][$questionIndex]['options'] = array_values($this->topics[$topicIndex]['quiz']['questions'][$questionIndex]['options']);
        }
    }

    /* --------------------------- Post Test Methods ------------------------- */
    public function addPostQuestionRow(): void
    {
        $this->postQuestions[] = [
            'type' => 'multiple',
            'question' => '',
            'options' => [''],
        ];
    }

    public function removePostQuestionRow(int $index): void
    {
        if (isset($this->postQuestions[$index])) {
            unset($this->postQuestions[$index]);
            $this->postQuestions = array_values($this->postQuestions);
        }
    }

    public function addPostOptionRow(int $qIndex): void
    {
        if (isset($this->postQuestions[$qIndex]) && ($this->postQuestions[$qIndex]['type'] ?? '') === 'multiple') {
            $this->postQuestions[$qIndex]['options'][] = '';
        }
    }

    public function removePostOptionRow(int $qIndex, int $oIndex): void
    {
        if (isset($this->postQuestions[$qIndex]['options'][$oIndex])) {
            unset($this->postQuestions[$qIndex]['options'][$oIndex]);
            $this->postQuestions[$qIndex]['options'] = array_values($this->postQuestions[$qIndex]['options']);
        }
    }

    public function render()
    {
        return view('pages.courses.edit-course');
    }
}
