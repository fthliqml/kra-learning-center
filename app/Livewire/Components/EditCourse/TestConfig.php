<?php

namespace App\Livewire\Components\EditCourse;

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Test;
use Illuminate\Support\Facades\DB;

class TestConfig extends Component
{
    use Toast;

    public ?int $courseId = null;

    // Pretest settings
    public $pretest_passing_score = 75; // 0-100 (business rule: must be >=20 to save)
    public $pretest_max_attempts = null; // null => unlimited
    public bool $pretest_randomize_question = false; // show_result_immediately fixed in DB

    // Post test settings
    public $posttest_passing_score = 75; // 0-100 (business rule: must be >=20 to save)
    public $posttest_max_attempts = null;
    public bool $posttest_randomize_question = false; // show_result_immediately fixed in DB

    public bool $hasEverSaved = false;
    public bool $persisted = false;
    public bool $isDirty = false;
    protected string $originalHash = '';
    public ?int $pretestId = null;
    public ?int $posttestId = null;

    public function mount(): void
    {
        if ($this->courseId) {
            // Use explicit custom hydration method name to avoid clashing with Livewire's internal
            // lifecycle hook methods (e.g. hydrate()). Mirrors pattern used in PretestQuestions.
            $this->hydrateFromCourse();
        }
    }

    /**
     * Load existing pretest & posttest configuration from database.
     * Renamed from hydrate() -> hydrateFromCourse() to prevent collision with
     * Livewire's internal hydrate lifecycle hook which caused the
     * "hydrate does not exist" error in some contexts.
     */
    protected function hydrateFromCourse(): void
    {
        $pre = Test::where('course_id', $this->courseId)->where('type', 'pretest')->first();
        if ($pre) {
            $this->pretestId = $pre->id;
            $this->pretest_passing_score = $pre->passing_score;
            $this->pretest_max_attempts = $pre->max_attempts;
            $this->pretest_randomize_question = (bool) $pre->randomize_question;
            $this->hasEverSaved = true; // if at least one exists treat as saved
            $this->persisted = true;
        }
        $post = Test::where('course_id', $this->courseId)->where('type', 'posttest')->first();
        if ($post) {
            $this->posttestId = $post->id;
            $this->posttest_passing_score = $post->passing_score;
            $this->posttest_max_attempts = $post->max_attempts;
            $this->posttest_randomize_question = (bool) $post->randomize_question;
            $this->hasEverSaved = true;
            $this->persisted = true;
        }
        $this->snapshot(); // capture hash after loading
    }

    public function updated($prop): void
    {
        // Clamp passing scores immediately
        if ($prop === 'pretest_passing_score') {
            $this->pretest_passing_score = max(0, min(100, (int) $this->pretest_passing_score));
        } elseif ($prop === 'posttest_passing_score') {
            $this->posttest_passing_score = max(0, min(100, (int) $this->posttest_passing_score));
        }
        if ($this->hasEverSaved) {
            $this->persisted = false;
        }
        $this->computeDirty();
    }

    public function goBack(): void
    {
        $this->dispatch('setTab', 'post-test');
    }

    public function finish(): void
    {
        // Navigate back to courses management page (full redirect similar to CourseInfo::goManagement)
        $this->redirectRoute('courses-management.index', navigate: true);
    }

    public function saveDraft(): void
    {
        if (!$this->courseId) {
            $this->error('Course ID missing', timeout: 5000, position: 'toast-top toast-center');
            return;
        }

        // Basic validation
        $this->pretest_passing_score = (int) $this->pretest_passing_score;
        $this->posttest_passing_score = (int) $this->posttest_passing_score;

        if ($this->pretest_passing_score < 0 || $this->pretest_passing_score > 100) {
            $this->error('Pretest passing score must be between 0 and 100', timeout: 6000, position: 'toast-top toast-center');
            return;
        }
        if ($this->pretest_passing_score < 20) {
            $this->error('Pretest passing score must be at least 20', timeout: 6000, position: 'toast-top toast-center');
            return;
        }
        if ($this->posttest_passing_score < 0 || $this->posttest_passing_score > 100) {
            $this->error('Post test passing score must be between 0 and 100', timeout: 6000, position: 'toast-top toast-center');
            return;
        }
        if ($this->posttest_passing_score < 20) {
            $this->error('Post test passing score must be at least 20', timeout: 6000, position: 'toast-top toast-center');
            return;
        }
        if ($this->pretest_max_attempts !== null && $this->pretest_max_attempts !== '') {
            $this->pretest_max_attempts = (int) $this->pretest_max_attempts;
            if ($this->pretest_max_attempts < 1) {
                $this->error('Pretest max attempts must be >= 1 or empty for unlimited', timeout: 6000, position: 'toast-top toast-center');
                return;
            }
        } else {
            $this->pretest_max_attempts = null;
        }
        if ($this->posttest_max_attempts !== null && $this->posttest_max_attempts !== '') {
            $this->posttest_max_attempts = (int) $this->posttest_max_attempts;
            if ($this->posttest_max_attempts < 1) {
                $this->error('Post test max attempts must be >= 1 or empty for unlimited', timeout: 6000, position: 'toast-top toast-center');
                return;
            }
        } else {
            $this->posttest_max_attempts = null;
        }

        DB::transaction(function () {
            // PRETEST
            if ($this->pretestId) {
                $pre = Test::where('id', $this->pretestId)->where('course_id', $this->courseId)->where('type', 'pretest')->first();
            } else {
                $pre = null;
            }
            if (!$pre) {
                $pre = Test::create([
                    'course_id' => $this->courseId,
                    'type' => 'pretest',
                    'passing_score' => $this->pretest_passing_score,
                    'max_attempts' => $this->pretest_max_attempts,
                    'randomize_question' => $this->pretest_randomize_question,
                    'show_result_immediately' => true,
                ]);
                $this->pretestId = $pre->id;
            } else {
                $pre->update([
                    'passing_score' => $this->pretest_passing_score,
                    'max_attempts' => $this->pretest_max_attempts,
                    'randomize_question' => $this->pretest_randomize_question,
                ]);
            }

            // POSTTEST
            if ($this->posttestId) {
                $post = Test::where('id', $this->posttestId)->where('course_id', $this->courseId)->where('type', 'posttest')->first();
            } else {
                $post = null;
            }
            if (!$post) {
                $post = Test::create([
                    'course_id' => $this->courseId,
                    'type' => 'posttest',
                    'passing_score' => $this->posttest_passing_score,
                    'max_attempts' => $this->posttest_max_attempts,
                    'randomize_question' => $this->posttest_randomize_question,
                    'show_result_immediately' => true,
                ]);
                $this->posttestId = $post->id;
            } else {
                $post->update([
                    'passing_score' => $this->posttest_passing_score,
                    'max_attempts' => $this->posttest_max_attempts,
                    'randomize_question' => $this->posttest_randomize_question,
                ]);
            }
        });

        $this->hasEverSaved = true;
        $this->persisted = true;
        $this->snapshot();
        $this->success('Test configuration saved', timeout: 4000, position: 'toast-top toast-center');
    }

    protected function hashState(): string
    {
        $data = [
            'pretest' => [
                'passing_score' => (int) $this->pretest_passing_score,
                'max_attempts' => $this->pretest_max_attempts === null ? null : (int) $this->pretest_max_attempts,
                'randomize_question' => (bool) $this->pretest_randomize_question,
            ],
            'posttest' => [
                'passing_score' => (int) $this->posttest_passing_score,
                'max_attempts' => $this->posttest_max_attempts === null ? null : (int) $this->posttest_max_attempts,
                'randomize_question' => (bool) $this->posttest_randomize_question,
            ],
        ];
        return md5(json_encode($data));
    }

    protected function snapshot(): void
    {
        $this->originalHash = $this->hashState();
        $this->isDirty = false;
        if (!$this->hasEverSaved) {
            $this->persisted = false;
        }
    }

    protected function computeDirty(): void
    {
        $this->isDirty = $this->hashState() !== $this->originalHash;
        if ($this->isDirty) {
            $this->persisted = false;
        }
    }


    public function render()
    {
        return view('components.edit-course.test-config');
    }

    /**
     * Placeholder skeleton while component hydrates.
     */
    public function placeholder()
    {
        return <<<'HTML'
        <div class="p-6 space-y-6 animate-pulse">
            <div class="h-7 w-60 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="flex flex-wrap gap-4">
                        <div class="h-10 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-10 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-10 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                    <div class="h-3 w-40 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
                <div class="space-y-4">
                    <div class="h-4 w-36 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="flex flex-wrap gap-4">
                        <div class="h-10 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-10 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-10 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                    <div class="h-3 w-48 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
            <div class="space-y-3">
                <div class="h-4 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-24 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="flex gap-3">
                <div class="h-10 w-36 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-10 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        </div>
        HTML;
    }
}
