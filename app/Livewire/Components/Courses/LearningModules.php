<?php

namespace App\Livewire\Components\Courses;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class LearningModules extends Component
{
  use WithFileUploads;

  /**
   * Data structure (new):
   * $topics = [
   *   [
   *     'id' => uuid,
   *     'title' => string,
   *     'sections' => [
   *        [
   *          'id' => uuid,
   *          'title' => string,
   *          'resources' => [ ['type'=>'pdf','file'=>UploadedFile|null], ['type'=>'youtube','url'=>''] ],
   *          'quiz' => ['enabled'=>bool,'questions'=>[ ['id'=>uuid,'type'=>'multiple|essay','question'=>'','options'=>[]] ]]
   *        ],
   *        ...
   *     ]
   *   ], ...]
   */
  public array $topics = [];
  // Track collapsed topic IDs (UI state persist across requests)
  public array $collapsedTopicIds = [];

  // Dirty tracking flags (similar to CourseInfo component)
  protected string $originalHash = '';
  public bool $isDirty = false;
  public bool $hasEverSaved = false; // at least one successful save
  public bool $persisted = false;    // reflects last known DB persistence state

  public function mount(): void
  {
    // Backward compatibility / migration: older shape had topics as what are now sections
    // Detect old shape: topic has 'resources' key directly
    if (!empty($this->topics) && isset($this->topics[0]['resources'])) {
      $this->topics = [
        [
          'id' => Str::uuid()->toString(),
          'title' => '',
          'sections' => array_map(function ($oldSection) {
            // Ensure quiz structure completeness
            $quiz = $oldSection['quiz'] ?? ['enabled' => false, 'questions' => []];
            $quiz['enabled'] = (bool) ($quiz['enabled'] ?? false);
            $quiz['questions'] = $quiz['questions'] ?? [];
            return [
              'id' => Str::uuid()->toString(),
              'title' => $oldSection['title'] ?? '',
              'resources' => $oldSection['resources'] ?? [],
              'quiz' => $quiz,
            ];
          }, $this->topics),
        ],
      ];
    }

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

    // Normalize existing pdf resources from ['type'=>'pdf','file'=>...] to ['type'=>'pdf','url'=>''] if needed
    foreach ($this->topics as &$topic) {
      if (!isset($topic['sections']) || !is_array($topic['sections']))
        continue;
      foreach ($topic['sections'] as &$section) {
        if (!isset($section['resources']) || !is_array($section['resources']))
          continue;
        foreach ($section['resources'] as &$res) {
          if (($res['type'] ?? null) === 'pdf') {
            // If it has 'file' key but no 'url', convert placeholder to empty url
            if (isset($res['file']) && !isset($res['url'])) {
              $res['url'] = '';
            }
            // Remove file key to unify shape
            if (isset($res['file']))
              unset($res['file']);
          }
        }
        unset($res);
      }
      unset($section);
    }
    unset($topic);

    // Take initial snapshot for dirty tracking
    $this->snapshot();
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
      $this->computeDirty();
    }
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
      } elseif ($type === 'multiple' && empty($this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'])) {
        $this->topics[$t]['sections'][$s]['quiz']['questions'][$q]['options'] = [''];
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
        } catch (\Throwable $e) {
          // Reset on failure
          $res['url'] = '';
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
    // TODO: persist modules to DB (e.g., related tables) - stub for now
    $this->dispatch('notify', type: 'success', message: 'Modules draft saved');
    $this->snapshot();
    $this->hasEverSaved = true;
    $this->persisted = true;
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
}
