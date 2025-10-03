<?php

namespace App\Livewire\Components\EditCourse;

use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;

class PretestQuestions extends Component
{
    use Toast;
    public array $questions = [];
    // Parent should pass course id to enable persistence (same pattern as LearningModules)
    public ?int $courseId = null;
    // Simple save draft flags (no dirty tracking to avoid overhead with large arrays)
    public bool $hasEverSaved = false;
    public bool $persisted = false; // mimic persistence success
    // Error highlighting: indexes of invalid questions
    public array $errorQuestionIndexes = [];

    protected $listeners = [];

    public function mount(): void
    {
        if ($this->courseId) {
            $this->hydrateFromCourse();
        }
        if (empty($this->questions)) {
            $this->questions = [$this->makeQuestion()];
        }
    }

    /**
     * Hydrate existing pretest (type=pretest) into in-memory questions array structure.
     */
    protected function hydrateFromCourse(): void
    {
        $existingTest = Test::where('course_id', $this->courseId)
            ->where('type', 'pretest')
            ->first();
        if (!$existingTest) {
            $this->questions = [];
            return;
        }
        // Mark as previously saved so status component does not show 'Not saved yet'
        $this->hasEverSaved = true;
        $this->persisted = true;
        $loaded = [];
        $existingTest->load(['questions.options']);
        foreach ($existingTest->questions->sortBy('order')->values() as $qModel) {
            $options = $qModel->options->sortBy('order')->values();
            $opts = $options->map(fn($o) => $o->text)->all();
            $answerIndex = null;
            foreach ($options as $idx => $opt) {
                if ($opt->is_correct) {
                    $answerIndex = $idx;
                    break; // first correct (only one expected)
                }
            }
            $loaded[] = [
                'id' => (string) $qModel->id,
                'type' => $qModel->question_type ?? 'multiple',
                'question' => $qModel->text ?? '',
                'options' => ($qModel->question_type === 'multiple') ? $opts : [],
                'answer' => ($qModel->question_type === 'multiple') ? $answerIndex : null,
                'answer_nonce' => 0,
            ];
        }
        $this->questions = $loaded;
    }

    private function makeQuestion(string $type = 'multiple'): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'type' => $type,
            'question' => '',
            'options' => $type === 'multiple' ? [''] : [],
            'answer' => null, // index of correct option (multiple only)
            'answer_nonce' => 0, // forces radio group remount on reset
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
            if (($this->questions[$qIndex]['type'] ?? '') === 'multiple') {
                // adjust answer index
                if (!isset($this->questions[$qIndex]['answer_nonce'])) {
                    $this->questions[$qIndex]['answer_nonce'] = 0;
                }
                $ans =& $this->questions[$qIndex]['answer'];
                if ($ans !== null) {
                    if ($ans == $oIndex) { // removed the selected answer
                        $ans = null;
                        $this->questions[$qIndex]['answer_nonce']++;
                    } elseif ($ans > $oIndex) {
                        $ans -= 1; // shift left
                    }
                }
            }
        }
    }

    public function updated($prop): void
    {
        if (!is_array($prop) && preg_match('/^questions\\.(\d+)\\.type$/', $prop, $m)) {
            $i = (int) $m[1];
            $type = $this->questions[$i]['type'] ?? null;
            if ($type === 'essay') {
                $this->questions[$i]['options'] = [];
                $this->questions[$i]['answer'] = null;
            } elseif ($type === 'multiple' && empty($this->questions[$i]['options'])) {
                $this->questions[$i]['options'] = [''];
                $this->questions[$i]['answer'] = null;
            }
        }
    }

    public function setCorrectAnswer(int $qIndex, int $oIndex): void
    {
        if (!isset($this->questions[$qIndex]))
            return;
        $q =& $this->questions[$qIndex];
        if (($q['type'] ?? '') !== 'multiple')
            return;
        if (!isset($q['options'][$oIndex]))
            return;
        $current = $q['answer'] ?? null;
        $new = ($current === $oIndex) ? null : $oIndex; // toggle
        $q['answer'] = $new;
        if (!isset($q['answer_nonce']))
            $q['answer_nonce'] = 0;
        if ($new === null) {
            $q['answer_nonce']++;
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

        if (!$this->courseId) {
            $this->error(
                'Course ID not found',
                timeout: 5000,
                position: 'toast-top toast-center'
            );
            return;
        }

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

        DB::transaction(function () {
            // Fetch or create the pretest container row
            $test = Test::firstOrCreate(
                [
                    'course_id' => $this->courseId,
                    'type' => 'pretest',
                ],
                [
                    'passing_score' => 0, // default; can be updated via separate UI later
                    'max_attempts' => null,
                    'randomize_question' => false,
                    'show_result_immediately' => true,
                ]
            );

            // Wipe existing questions (cascade deletes options)
            $test->questions()->delete();

            foreach ($this->questions as $qOrder => $q) {
                $questionModel = TestQuestion::create([
                    'test_id' => $test->id,
                    'question_type' => in_array($q['type'] ?? '', ['multiple', 'essay']) ? $q['type'] : 'multiple',
                    'text' => $q['question'] ?: 'Untitled Question',
                    'order' => $qOrder,
                    'max_points' => 1,
                ]);

                if (($q['type'] ?? '') === 'multiple') {
                    $answerIndex = $q['answer'] ?? null;
                    foreach (($q['options'] ?? []) as $optIndex => $optText) {
                        $optText = trim($optText);
                        if ($optText === '')
                            continue; // skip empty placeholder
                        TestQuestionOption::create([
                            'question_id' => $questionModel->id,
                            'text' => $optText,
                            'order' => $optIndex,
                            'is_correct' => ($answerIndex !== null && $answerIndex === $optIndex),
                        ]);
                    }
                }
            }
        });

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
