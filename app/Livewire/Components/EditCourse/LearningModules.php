<?php

namespace App\Livewire\Components\EditCourse;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use App\Models\Topic;
use App\Models\Section;
use App\Models\ResourceItem;
use App\Models\SectionQuizQuestion;
use App\Models\SectionQuizQuestionOption;
use Mary\Traits\Toast;
use App\Services\LearningModulesValidator;

class LearningModules extends Component
{
    use WithFileUploads, Toast;

    public array $topics = [];
    /**
     * View / partial iteration helper variable legend (used inside Blade partials):
     *  $ti  : topic index (0-based)
     *  $topic : current topic array ['id','title','sections'=>[]]
     *  $si  : section index within current topic (0-based)
     *  $section : current section array ['id','title','resources'=>[],'quiz'=>['enabled'=>bool,'questions'=>[]]]
     *  $ri  : resource index within current section (0-based)
     *  $res : current resource array ['type'=>'pdf|youtube','url'=>string]
     *  $qi  : quiz question index within section (0-based)
     *  $qq  : current quiz question array ['id','type','question','options'=>[], 'answer'=>int|null, 'answer_nonce'=>int]
     *  $answerIndex : selected correct option index for multiple-choice question
     *  $answerNonce : integer incremented to force radio group remount after structural changes (option removed / answer unset)
     *
     * Error highlight key schemes (mirrors validator output):
     *  Topic key    => t{ti}
     *  Section key  => t{ti}-s{si}
     *  Resource key => t{ti}-s{si}-r{ri}
     *  Question key => t{ti}-s{si}-q{qi}
     * These keys are collected into $errorTopicKeys, $errorSectionKeys, $errorResourceKeys, $errorQuestionKeys arrays
     * for conditional CSS classes in Blade.
     *
     * Note: Indices remain 0-based in data/state; UI adds +1 where human-readable numbering is displayed.
     */
    public ?int $courseId = null; // Expect parent to pass course id
    // Track collapsed topic IDs (UI state persist across requests)
    public array $collapsedTopicIds = [];

    // Dirty tracking flags (similar to CourseInfo component)
    protected string $originalHash = '';
    public bool $isDirty = false;
    public bool $hasEverSaved = false; // at least one successful save
    public bool $persisted = false;    // reflects last known DB persistence state
    // UI error highlighting (populated during validation when save fails)
    public array $errorQuestionKeys = []; // keys like t0-s1-q2
    public array $errorResourceKeys = []; // keys like t0-s1-r3
    public array $errorSectionKeys = [];  // keys like t0-s1
    public array $errorTopicKeys = [];    // keys like t0

    public function mount(): void
    {
        // If courseId is provided, hydrate from database (overrides any pre-bound $topics)
        if ($this->courseId) {
            $this->hydrateFromCourse();
        }

        // Backward compatibility only if NOT hydrated and old flat shape supplied
        if (empty($this->topics) && !empty($this->topics) && isset($this->topics[0]['resources'])) {
            // (Edge: this block is effectively unreachable now because condition is contradictory; retained for reference)
        }

        // If still empty (no course data), bootstrap a minimal blank structure
        if (empty($this->topics)) {
            $this->topics = [
                [
                    'id' => Str::uuid()->toString(),
                    'title' => '',
                    'sections' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'title' => '',
                            'resources' => [],
                            'quiz' => [
                                'enabled' => false,
                                'questions' => [],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $this->normalizePdfResources();
        $this->snapshot();
    }

    /**
     * Normalize pdf resource shape to always have ['type'=>'pdf','url'=>''] (remove transient 'file').
     */
    protected function normalizePdfResources(): void
    {
        foreach ($this->topics as &$topic) {
            if (!isset($topic['sections']) || !is_array($topic['sections']))
                continue;
            foreach ($topic['sections'] as &$section) {
                if (!isset($section['resources']) || !is_array($section['resources']))
                    continue;
                foreach ($section['resources'] as &$res) {
                    if (($res['type'] ?? null) === 'pdf') {
                        if (isset($res['file']) && !isset($res['url'])) {
                            $res['url'] = '';
                        }
                        if (!isset($res['filename']) || !is_string($res['filename']) || $res['filename'] === '') {
                            // derive from URL if possible
                            $res['filename'] = '';
                            if (!empty($res['url'])) {
                                $parts = explode('/', parse_url($res['url'], PHP_URL_PATH) ?? '');
                                $last = end($parts);
                                if (is_string($last) && str_contains($last, '.')) {
                                    $res['filename'] = $last;
                                }
                            }
                        }
                        if (isset($res['file']))
                            unset($res['file']);
                    }
                }
                unset($res);
            }
            unset($section);
        }
        unset($topic);
    }

    /**
     * Hydrate $topics structure from database for the given courseId.
     * Shape is aligned with in-memory editing format.
     */
    protected function hydrateFromCourse(): void
    {
        $loaded = [];
        $topicModels = Topic::where('course_id', $this->courseId)->orderBy('id')->get();
        if ($topicModels->isEmpty()) {
            $this->topics = [];
            return; // leave empty -> mount() will fill default
        }

        // Preload sections grouped by topic
        $topicIds = $topicModels->pluck('id')->all();
        $sectionModels = Section::whereIn('topic_id', $topicIds)->orderBy('id')->get()->groupBy('topic_id');
        $sectionIds = $sectionModels->flatten()->pluck('id')->all();
        $resourceModels = ResourceItem::whereIn('section_id', $sectionIds)->orderBy('id')->get()->groupBy('section_id');
        $questionModels = SectionQuizQuestion::whereIn('section_id', $sectionIds)->orderBy('order')->orderBy('id')->get()->groupBy('section_id');
        $questionIds = $questionModels->flatten()->pluck('id')->all();
        $optionModels = SectionQuizQuestionOption::whereIn('question_id', $questionIds)->orderBy('order')->orderBy('id')->get()->groupBy('question_id');

        foreach ($topicModels as $tModel) {
            $topicSections = [];
            /** @var \Illuminate\Support\Collection $sectionsForTopic */
            $sectionsForTopic = $sectionModels->get($tModel->id, collect());
            foreach ($sectionsForTopic as $sModel) {
                $resArray = [];
                $resourcesForSection = $resourceModels->get($sModel->id, collect());
                foreach ($resourcesForSection as $rModel) {
                    $type = $rModel->content_type === 'yt' ? 'youtube' : $rModel->content_type; // map back
                    $url = $rModel->url ?? '';
                    $filename = '';
                    if ($type === 'pdf') {
                        if (!empty($rModel->filename)) {
                            $filename = $rModel->filename;
                        } elseif ($url) {
                            $parts = explode('/', parse_url($url, PHP_URL_PATH) ?? '');
                            $last = end($parts);
                            if (is_string($last) && str_contains($last, '.')) {
                                $filename = $last;
                            }
                        }
                    }
                    $resArray[] = [
                        'type' => $type,
                        'url' => $url,
                        'filename' => $filename,
                    ];
                }

                // Quiz questions
                $qArray = [];
                $questionsForSection = $questionModels->get($sModel->id, collect());
                foreach ($questionsForSection as $qModel) {
                    $opts = [];
                    $answerIndex = null;
                    $optionsForQuestion = $optionModels->get($qModel->id, collect());
                    foreach ($optionsForQuestion as $idx => $optModel) {
                        $opts[] = $optModel->option;
                        if ($answerIndex === null && $optModel->is_correct) {
                            $answerIndex = $idx; // first correct option
                        }
                    }
                    $qArray[] = [
                        'id' => (string) $qModel->id, // keep as string for consistency with uuid style
                        'type' => $qModel->type ?? 'multiple',
                        'question' => $qModel->question ?? '',
                        'options' => $qModel->type === 'multiple' ? $opts : [],
                        'answer' => $qModel->type === 'multiple' ? $answerIndex : null,
                        'answer_nonce' => 0,
                    ];
                }

                $topicSections[] = [
                    'id' => (string) $sModel->id,
                    'title' => $sModel->title ?? '',
                    'resources' => $resArray,
                    'quiz' => [
                        'enabled' => (bool) ($sModel->is_quiz_on || count($qArray) > 0),
                        'questions' => $qArray,
                    ],
                ];
            }
            $loaded[] = [
                'id' => (string) $tModel->id,
                'title' => $tModel->title ?? '',
                'sections' => $topicSections,
            ];
        }

        $this->topics = $loaded;
    }

    private function makeQuestion(string $type = 'multiple'): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'type' => $type,
            'question' => '',
            'options' => $type === 'multiple' ? [''] : [],
            'answer' => null, // index of correct option (for multiple)
            'answer_nonce' => 0, // bump to force radio group remount when answer reset
        ];
    }

    /* Topics CRUD */
    public function addTopic(): void
    {
        $this->topics[] = [
            'id' => Str::uuid()->toString(),
            'title' => '',
            'sections' => [
                [
                    'id' => Str::uuid()->toString(),
                    'title' => '',
                    'resources' => [],
                    'quiz' => ['enabled' => false, 'questions' => []],
                ],
            ],
        ];
        $this->computeDirty();
    }

    public function removeTopic(int $index): void
    {
        if (isset($this->topics[$index])) {
            $removedId = $this->topics[$index]['id'] ?? null;
            unset($this->topics[$index]);
            $this->topics = array_values($this->topics);
            if ($removedId) {
                $this->collapsedTopicIds = array_values(array_filter($this->collapsedTopicIds, fn($id) => $id !== $removedId));
            }
            $this->computeDirty();
        }
    }

    /** Persist collapsed/expanded state from front-end */
    public function setCollapsedTopic(string $topicId, bool $collapsed): void
    {
        if ($collapsed) {
            if (!in_array($topicId, $this->collapsedTopicIds, true)) {
                $this->collapsedTopicIds[] = $topicId;
            }
        } else {
            $this->collapsedTopicIds = array_values(array_filter($this->collapsedTopicIds, fn($id) => $id !== $topicId));
        }
    }

    public function isTopicCollapsed(string $topicId): bool
    {
        return in_array($topicId, $this->collapsedTopicIds, true);
    }

    /* Sections CRUD */
    public function addSection(int $topicIndex): void
    {
        if (!isset($this->topics[$topicIndex]))
            return;
        $this->topics[$topicIndex]['sections'][] = [
            'id' => Str::uuid()->toString(),
            'title' => '',
            'resources' => [],
            'quiz' => ['enabled' => false, 'questions' => []],
        ];
        $this->computeDirty();
    }

    public function removeSection(int $topicIndex, int $sectionIndex): void
    {
        if (isset($this->topics[$topicIndex]['sections'][$sectionIndex])) {
            unset($this->topics[$topicIndex]['sections'][$sectionIndex]);
            $this->topics[$topicIndex]['sections'] = array_values($this->topics[$topicIndex]['sections']);
            $this->computeDirty();
        }
    }

    /* Resources (sections) */
    public function addSectionResource(int $topicIndex, int $sectionIndex, string $type): void
    {
        if (!isset($this->topics[$topicIndex]['sections'][$sectionIndex]))
            return;
        if ($type === 'pdf') {
            $this->topics[$topicIndex]['sections'][$sectionIndex]['resources'][] = ['type' => 'pdf', 'file' => null, 'url' => ''];
        } elseif ($type === 'youtube') {
            $this->topics[$topicIndex]['sections'][$sectionIndex]['resources'][] = ['type' => 'youtube', 'url' => ''];
        }
        $this->computeDirty();
    }

    public function removeSectionResource(int $topicIndex, int $sectionIndex, int $resourceIndex): void
    {
        if (isset($this->topics[$topicIndex]['sections'][$sectionIndex]['resources'][$resourceIndex])) {
            unset($this->topics[$topicIndex]['sections'][$sectionIndex]['resources'][$resourceIndex]);
            $this->topics[$topicIndex]['sections'][$sectionIndex]['resources'] = array_values($this->topics[$topicIndex]['sections'][$sectionIndex]['resources']);
            $this->computeDirty();
        }
    }

    /* Quiz (sections) */
    public function toggleSectionQuiz(int $topicIndex, int $sectionIndex): void
    {
        if (!isset($this->topics[$topicIndex]['sections'][$sectionIndex]))
            return;
        $enabled = (bool) ($this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['enabled'] ?? false);
        $this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['enabled'] = !$enabled;
        if ($this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['enabled'] && empty($this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['questions'])) {
            $this->addSectionQuizQuestion($topicIndex, $sectionIndex);
        }
        $this->computeDirty();
    }

    public function addSectionQuizQuestion(int $topicIndex, int $sectionIndex): void
    {
        if (!isset($this->topics[$topicIndex]['sections'][$sectionIndex]))
            return;
        $this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['questions'][] = $this->makeQuestion();
        $this->computeDirty();
    }

    public function removeSectionQuizQuestion(int $topicIndex, int $sectionIndex, int $questionIndex): void
    {
        if (isset($this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['questions'][$questionIndex])) {
            unset($this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['questions'][$questionIndex]);
            $this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['questions'] = array_values($this->topics[$topicIndex]['sections'][$sectionIndex]['quiz']['questions']);
            $this->computeDirty();
        }
    }

    public function addSectionQuizOption(int $t, int $s, int $q): void
    {
        if (isset($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]) && ($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['type'] ?? '') === 'multiple') {
            $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'][] = '';
            $this->computeDirty();
        }
    }

    public function removeSectionQuizOption(int $t, int $s, int $q, int $o): void
    {
        if (isset($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'][$o])) {
            unset($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'][$o]);
            $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'] = array_values($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options']);
            // Adjust answer index if needed
            $answer =& $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['answer'];
            if (!isset($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['answer_nonce'])) {
                $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['answer_nonce'] = 0;
            }
            $nonce =& $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['answer_nonce'];
            if ($answer !== null) {
                // Reset ANY time the removed index is the answer OR shifts ordering before it
                if ($answer >= $o) {
                    $answer = null; // force user to re-confirm correct answer after structural change
                    $nonce++; // force radios remount
                }
            }
            $this->computeDirty();
        }
    }

    public function setCorrectAnswer(int $t, int $s, int $q, int $o): void
    {
        if (!isset($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]))
            return;
        $question =& $this->topics[$t]['sections'][$s]['quiz']['questions'][$q];
        if (($question['type'] ?? '') !== 'multiple')
            return;
        if (!isset($question['options'][$o]))
            return;
        $current = $question['answer'] ?? null;
        // Toggle behavior: click again to unset
        $newAnswer = ($current === $o) ? null : $o;
        $question['answer'] = $newAnswer;
        if (!isset($question['answer_nonce'])) {
            $question['answer_nonce'] = 0;
        }
        if ($newAnswer === null) {
            // bump nonce so blade radio name/key changes -> browser clears checked state
            $question['answer_nonce']++;
        }
        $this->computeDirty();
    }

    public function updated($prop): void
    {
        if (!is_array($prop) && preg_match('/^topics\.(\d+)\.sections\.(\d+)\.quiz\.questions\.(\d+)\.type$/', $prop, $m)) {
            $t = (int) $m[1];
            $s = (int) $m[2];
            $q = (int) $m[3];
            $type = $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['type'] ?? null;
            if ($type === 'essay') {
                $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'] = [];
                $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['answer'] = null;
            } elseif ($type === 'multiple' && empty($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'])) {
                $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'] = [''];
                $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['answer'] = null;
            }
            $this->computeDirty();
        }

        // Handle PDF upload -> store -> set public URL
        if (!is_array($prop) && preg_match('/^topics\.(\d+)\.sections\.(\d+)\.resources\.(\d+)\.file$/', $prop, $m2)) {
            $t = (int) $m2[1];
            $s = (int) $m2[2];
            $r = (int) $m2[3];
            $res =& $this->topics[$t]['sections'][$s]['resources'][$r];
            if (($res['type'] ?? null) === 'pdf' && isset($res['file']) && $res['file']) {
                try {
                    $storedPath = $res['file']->store('modules/pdf', 'public');
                    $res['url'] = storage_path('app/public/' . $storedPath) ? asset('storage/' . $storedPath) : '';
                    // Preserve original client filename
                    try {
                        $originalName = method_exists($res['file'], 'getClientOriginalName') ? $res['file']->getClientOriginalName() : null;
                        if ($originalName) {
                            $res['filename'] = $originalName;
                        } else {
                            // Fallback derive from stored path
                            $res['filename'] = basename($storedPath);
                        }
                    } catch (\Throwable $e) {
                        $res['filename'] = basename($storedPath);
                    }
                } catch (\Throwable $e) {
                    // Reset on failure
                    $res['url'] = '';
                    $res['filename'] = '';
                }
                // Remove the file key after storing to keep hash stable
                unset($res['file']);
                $this->computeDirty();
            }
        }
    }

    /* Reordering */
    public function reorderTopics(array $orderedIds): void
    {
        if (empty($orderedIds))
            return;
        $map = [];
        foreach ($this->topics as $t) {
            $map[$t['id']] = $t;
        }
        $new = [];
        foreach ($orderedIds as $id) {
            if (isset($map[$id])) {
                $new[] = $map[$id];
                unset($map[$id]);
            }
        }
        // append leftovers (if any)
        foreach ($map as $left)
            $new[] = $left;
        $this->topics = $new;
        $this->computeDirty();
    }

    public function reorderSections(int $topicIndex, array $orderedIds): void
    {
        if (!isset($this->topics[$topicIndex]) || empty($orderedIds))
            return;
        $sections = $this->topics[$topicIndex]['sections'] ?? [];
        $map = [];
        foreach ($sections as $sec) {
            $map[$sec['id']] = $sec;
        }
        $new = [];
        foreach ($orderedIds as $id) {
            if (isset($map[$id])) {
                $new[] = $map[$id];
                unset($map[$id]);
            }
        }
        foreach ($map as $left)
            $new[] = $left;
        $this->topics[$topicIndex]['sections'] = $new;
        $this->computeDirty();
    }

    /* Dirty tracking helpers */
    protected function sanitizedTopics(): array
    {
        $clone = $this->topics;
        foreach ($clone as &$topic) {
            if (!isset($topic['sections']) || !is_array($topic['sections']))
                continue;
            foreach ($topic['sections'] as &$section) {
                if (!isset($section['resources']) || !is_array($section['resources']))
                    continue;
                foreach ($section['resources'] as &$res) {
                    if (isset($res['file']))
                        unset($res['file']); // remove transient upload refs
                }
                unset($res);
            }
            unset($section);
        }
        unset($topic);
        return $clone;
    }

    protected function hashState(): string
    {
        return md5(json_encode($this->sanitizedTopics()));
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

    public function saveDraft(): void
    {
        if (!$this->courseId) {
            $this->error(
                'Course ID not found',
                timeout: 5000,
                position: 'toast-top toast-center'
            );
            return;
        }

        // Structural validation via external service (no in-place pruning; errors surfaced with highlight arrays)
        $validator = new LearningModulesValidator();
        $result = $validator->validate($this->topics);
        $errors = $result['errors'];
        $this->errorTopicKeys = $result['errorTopicKeys'];
        $this->errorSectionKeys = $result['errorSectionKeys'];
        $this->errorResourceKeys = $result['errorResourceKeys'];
        $this->errorQuestionKeys = $result['errorQuestionKeys'];

        if (!empty($errors)) {
            // Build bullet list, limit to 6 then append summary line
            $bulletLines = collect($errors)->take(6)->map(fn($e) => '• ' . $e);
            $display = $bulletLines->implode("\n");
            if (count($errors) > 6) {
                $display .= "\n..." . (count($errors) - 6) . " more errors";
            }

            // Wrap in a div that enforces preserved newlines (Toast should allow simple HTML)
            $htmlMessage = "<div style=\"white-space:pre-line; text-align:left\"><strong>Validation failed:</strong>\n" . e($display) . '</div>';

            // Some toast implementations escape content; if so line breaks will be lost. We attempt HTML first, fallback plain.
            $this->error(
                $htmlMessage,
                timeout: 10000,
                position: 'toast-top toast-center'
            );
            return;
        }

        // After pruning, if absolutely nothing remains, block save (avoid silently "successful" empty draft)
        if (empty($this->topics)) {
            $this->error(
                'Cannot save empty modules. Please add at least one topic, section, resource or quiz question before saving.',
                timeout: 6000,
                position: 'toast-top toast-center'
            );
            // Rehydrate a minimal blank structure so UI still has something to render
            $this->topics = [
                [
                    'id' => Str::uuid()->toString(),
                    'title' => '',
                    'sections' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'title' => '',
                            'resources' => [],
                            'quiz' => [
                                'enabled' => false,
                                'questions' => [],
                            ],
                        ],
                    ],
                ],
            ];
            // Do not snapshot; keep dirty so user can start filling content
            return;
        }

        // Basic trimming (optional)
        foreach ($this->topics as &$t) {
            $t['title'] = trim($t['title']);
            foreach (($t['sections'] ?? []) as &$sec) {
                $sec['title'] = trim($sec['title']);
                if (($sec['quiz']['enabled'] ?? false) && isset($sec['quiz']['questions'])) {
                    foreach ($sec['quiz']['questions'] as &$qq) {
                        $qq['question'] = trim($qq['question']);
                        if (($qq['type'] ?? '') === 'multiple') {
                            $qq['options'] = array_map(fn($o) => trim($o), $qq['options'] ?? []);
                        }
                    }
                    unset($qq);
                }
            }
            unset($sec);
        }
        unset($t);

        DB::transaction(function () {
            // Strategy: wipe & recreate (simpler for draft). Could be optimized later for diffing.
            Topic::where('course_id', $this->courseId)->delete();

            foreach ($this->topics as $topicOrder => $t) {
                $topicModel = Topic::create([
                    'course_id' => $this->courseId,
                    'title' => $t['title'] ?: 'Untitled Topic',
                ]);

                foreach (($t['sections'] ?? []) as $sectionOrder => $sec) {
                    $sectionModel = Section::create([
                        'topic_id' => $topicModel->id,
                        'title' => $sec['title'] ?: 'Untitled Section',
                        'is_quiz_on' => (bool) ($sec['quiz']['enabled'] ?? false),
                    ]);

                    // Resources
                    foreach (($sec['resources'] ?? []) as $resOrder => $res) {
                        $type = $res['type'] ?? null;
                        if (!$type)
                            continue;
                        $contentType = $type === 'youtube' ? 'yt' : $type; // map to enum in DB
                        $url = $res['url'] ?? '';
                        if ($contentType === 'pdf' && !$url) {
                            // skip empty pdf placeholder
                            continue;
                        }
                        ResourceItem::create([
                            'section_id' => $sectionModel->id,
                            'content_type' => $contentType,
                            'url' => $url,
                            'filename' => $contentType === 'pdf' ? ($res['filename'] ?? ($url ? basename(parse_url($url, PHP_URL_PATH)) : null)) : null,
                        ]);
                    }

                    // Quiz
                    if (($sec['quiz']['enabled'] ?? false) && !empty($sec['quiz']['questions'])) {
                        foreach ($sec['quiz']['questions'] as $qOrder => $qq) {
                            $questionModel = SectionQuizQuestion::create([
                                'section_id' => $sectionModel->id,
                                'type' => in_array($qq['type'] ?? '', ['multiple', 'essay']) ? $qq['type'] : 'multiple',
                                'question' => $qq['question'] ?: 'Untitled Question',
                                'order' => $qOrder,
                            ]);
                            if (($qq['type'] ?? '') === 'multiple') {
                                $answerIndex = $qq['answer'] ?? null;
                                foreach (($qq['options'] ?? []) as $optIndex => $optText) {
                                    if ($optText === '')
                                        continue; // skip empty
                                    SectionQuizQuestionOption::create([
                                        'question_id' => $questionModel->id,
                                        'option' => $optText,
                                        'is_correct' => ($answerIndex !== null && $answerIndex == $optIndex),
                                        'order' => $optIndex,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        });

        $this->success(
            'Modules draft saved successfully',
            timeout: 4000,
            position: 'toast-top toast-center'
        );
        $this->snapshot();
        $this->hasEverSaved = true;
        $this->persisted = true;
    }

    public function goNext(): void
    {
        dump($this->topics);
        $this->dispatch('setTab', 'post-test');
    }
    public function goBack(): void
    {
        $this->dispatch('setTab', 'pretest');
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="p-6 space-y-6 animate-pulse">
            <div class="h-6 w-60 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="space-y-4">
                <div class="h-5 w-48 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="space-y-3">
                    <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-10 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-10 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
            <div class="space-y-4">
                <div class="h-5 w-40 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="h-10 w-36 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-10 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        </div>
        HTML;
    }

    public function render()
    {
        return view('components.edit-course.learning-modules');
    }

}
