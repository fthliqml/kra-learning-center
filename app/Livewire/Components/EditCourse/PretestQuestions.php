<?php

namespace App\Livewire\Components\EditCourse;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use App\Exports\TestQuestionsTemplateExport;
use App\Imports\TestQuestionsImport;
use App\Services\PretestQuestionsValidator;
use Illuminate\Validation\ValidationException;

class PretestQuestions extends Component
{
  use Toast, WithFileUploads;

  public array $questions = [];
  public $file; // For file upload
  // Parent should pass course id to enable persistence (same pattern as LearningModules)
  public ?int $courseId = null;
  // Save draft flags with dirty tracking
  public bool $hasEverSaved = false;
  public bool $persisted = false; // mimic persistence success
  public bool $isDirty = false; // track unsaved changes
  protected string $originalHash = ''; // hash of saved state
  // Error highlighting: indexes of invalid questions
  public array $errorQuestionIndexes = [];

  // Test configuration
  public int $passingScore = 75;
  public ?int $maxAttempts = null;
  public bool $randomizeQuestion = false;

  // Listen for parent event when a new course draft gets created so we can attach pretest.
  protected $listeners = [
    'courseCreated' => 'onCourseCreated', // emitted with the new course id
  ];

  public function onCourseCreated(int $newCourseId): void
  {
    // Only set if we didn't already have a course id.
    if (!$this->courseId) {
      $this->courseId = $newCourseId;
      // Re-hydrate (will load existing pretest if any — typically none for brand new course)
      $this->hydrateFromCourse();
      // Force at least one question if still empty
      if (empty($this->questions)) {
        $this->questions = [$this->makeQuestion()];
      }
    }
  }

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

    // Load test config
    $this->passingScore = $existingTest->passing_score ?? 75;
    $this->maxAttempts = $existingTest->max_attempts;
    $this->randomizeQuestion = $existingTest->randomize_question ?? false;

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
        'max_points' => ($qModel->question_type === 'essay') ? ($qModel->max_points ?? 10) : null,
      ];
    }
    $this->questions = $loaded;
    $this->snapshot(); // Take snapshot after loading
  }

  protected function snapshot(): void
  {
    $this->originalHash = md5(json_encode([
      'questions' => $this->questions,
      'passingScore' => $this->passingScore,
      'maxAttempts' => $this->maxAttempts,
      'randomizeQuestion' => $this->randomizeQuestion,
    ]));
    $this->isDirty = false;
  }

  protected function computeDirty(): void
  {
    $currentHash = md5(json_encode([
      'questions' => $this->questions,
      'passingScore' => $this->passingScore,
      'maxAttempts' => $this->maxAttempts,
      'randomizeQuestion' => $this->randomizeQuestion,
    ]));
    $this->isDirty = $currentHash !== $this->originalHash;
    if ($this->isDirty) {
      $this->persisted = false;
    }
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
      'max_points' => $type === 'essay' ? 10 : null, // Essay has custom points, MC auto-calculated
    ];
  }

  public function addQuestion(): void
  {
    $this->questions[] = $this->makeQuestion();
    $this->computeDirty();
  }

  public function removeQuestion(int $index): void
  {
    if (isset($this->questions[$index])) {
      unset($this->questions[$index]);
      $this->questions = array_values($this->questions);
      $this->computeDirty();
    }
  }

  public function addOption(int $qIndex): void
  {
    if (isset($this->questions[$qIndex]) && $this->questions[$qIndex]['type'] === 'multiple') {
      $this->questions[$qIndex]['options'][] = '';
      $this->computeDirty();
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
        $ans = &$this->questions[$qIndex]['answer'];
        if ($ans !== null) {
          if ($ans == $oIndex) { // removed the selected answer
            $ans = null;
            $this->questions[$qIndex]['answer_nonce']++;
          } elseif ($ans > $oIndex) {
            $ans -= 1; // shift left
          }
        }
      }
      $this->computeDirty();
    }
  }

  public function updated($prop): void
  {
    // Handle question type changes
    if (!is_array($prop) && preg_match('/^questions\\.(\d+)\\.type$/', $prop, $m)) {
      $i = (int) $m[1];
      $type = $this->questions[$i]['type'] ?? null;
      if ($type === 'essay') {
        $this->questions[$i]['options'] = [];
        $this->questions[$i]['answer'] = null;
        if (!isset($this->questions[$i]['max_points'])) {
          $this->questions[$i]['max_points'] = 10;
        }
      } elseif ($type === 'multiple' && empty($this->questions[$i]['options'])) {
        $this->questions[$i]['options'] = [''];
        $this->questions[$i]['answer'] = null;
        $this->questions[$i]['max_points'] = null; // MC uses auto-calculated points
      }
    }

    // Track any changes to questions array for dirty state
    if (str_starts_with($prop, 'questions') || in_array($prop, ['passingScore', 'maxAttempts', 'randomizeQuestion'])) {
      $this->computeDirty();
    }
  }

  public function setCorrectAnswer(int $qIndex, int $oIndex): void
  {
    if (!isset($this->questions[$qIndex]))
      return;
    $q = &$this->questions[$qIndex];
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
    $this->computeDirty();
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
    $this->computeDirty();
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
      // Inform user they must save basic course info first (so parent can emit courseCreated afterwards)
      $this->error(
        'Save Course Info first to create the course draft before saving the Pretest.',
        timeout: 6000,
        position: 'toast-top toast-center'
      );
      return;
    }

    // Validate structure via service (mirrors LearningModules style)
    $validator = new PretestQuestionsValidator();
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
      $test = Test::updateOrCreate(
        [
          'course_id' => $this->courseId,
          'type' => 'pretest',
        ],
        [
          'passing_score' => $this->passingScore,
          'max_attempts' => $this->maxAttempts,
          'randomize_question' => $this->randomizeQuestion,
          'show_result_immediately' => true,
        ]
      );

      // Wipe existing questions (cascade deletes options)
      $test->questions()->delete();

      // Calculate points distribution
      $totalPoints = 100;
      $essayQuestions = array_filter($this->questions, fn($q) => ($q['type'] ?? '') === 'essay');
      $mcQuestions = array_filter($this->questions, fn($q) => ($q['type'] ?? '') === 'multiple');

      $essayTotalPoints = 0;
      foreach ($essayQuestions as $q) {
        $essayTotalPoints += (int) ($q['max_points'] ?? 10);
      }

      // Remaining points for MC questions
      $mcTotalPoints = max(0, $totalPoints - $essayTotalPoints);
      $mcCount = count($mcQuestions);
      $mcPointsEach = $mcCount > 0 ? round($mcTotalPoints / $mcCount, 2) : 0;

      foreach ($this->questions as $qOrder => $q) {
        $questionType = in_array($q['type'] ?? '', ['multiple', 'essay']) ? $q['type'] : 'multiple';
        $maxPoints = $questionType === 'essay' ? (int) ($q['max_points'] ?? 10) : $mcPointsEach;

        $questionModel = TestQuestion::create([
          'test_id' => $test->id,
          'question_type' => $questionType,
          'text' => $q['question'] ?: 'Untitled Question',
          'order' => $qOrder,
          'max_points' => $maxPoints,
        ]);

        if ($questionType === 'multiple') {
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
    $this->snapshot(); // Take snapshot after successful save
    $this->success(
      'Pretest questions saved successfully',
      timeout: 4000,
      position: 'toast-top toast-center'
    );
  }

  /**
   * Download template Excel file for importing questions.
   */
  public function downloadTemplate()
  {
    return response()->streamDownload(function () {
      echo Excel::raw(new TestQuestionsTemplateExport('pretest'), \Maatwebsite\Excel\Excel::XLSX);
    }, 'pretest_questions_template.xlsx');
  }

  /**
   * Handle file upload and import questions from Excel.
   */
  public function updatedFile()
  {
    if (!$this->file) {
      return;
    }

    try {
      $import = new TestQuestionsImport('pretest');
      Excel::import($import, $this->file->getRealPath());

      $importedQuestions = $import->getQuestions();

      if (empty($importedQuestions)) {
        $this->error(
          'No valid questions found in the uploaded file.',
          timeout: 6000,
          position: 'toast-top toast-center'
        );
        $this->reset('file');
        return;
      }

      // Merge imported questions with existing ones
      $this->questions = array_merge($this->questions, $importedQuestions);
      $this->computeDirty();

      $this->success(
        count($importedQuestions) . ' question(s) imported successfully.',
        timeout: 4000,
        position: 'toast-top toast-center'
      );
    } catch (ValidationException $e) {
      $errors = $e->errors()['import'] ?? [];
      $bulletLines = collect($errors)->take(6)->map(fn($err) => '• ' . $err);
      $display = $bulletLines->implode("\n");
      if (count($errors) > 6) {
        $display .= "\n..." . (count($errors) - 6) . " more errors";
      }
      $htmlMessage = "<div style=\"white-space:pre-line; text-align:left\"><strong>Import failed:</strong>\n" . e($display) . '</div>';
      $this->error(
        $htmlMessage,
        timeout: 10000,
        position: 'toast-top toast-center'
      );
    } catch (\Exception $e) {
      $this->error(
        'Failed to import file: ' . $e->getMessage(),
        timeout: 6000,
        position: 'toast-top toast-center'
      );
    }

    $this->reset('file');
  }

  public function placeholder()
  {
    return view('components.skeletons.pretest-questions');
  }

  public function render()
  {
    // Calculate points distribution for display
    $pointsInfo = $this->calculatePointsDistribution();

    return view('components.edit-course.pretest-questions', [
      'pointsInfo' => $pointsInfo,
    ]);
  }

  /**
   * Calculate points distribution between essay and MC questions
   */
  public function calculatePointsDistribution(): array
  {
    $totalPoints = 100;
    $essayQuestions = array_filter($this->questions, fn($q) => ($q['type'] ?? '') === 'essay');
    $mcQuestions = array_filter($this->questions, fn($q) => ($q['type'] ?? '') === 'multiple');

    $essayTotalPoints = 0;
    foreach ($essayQuestions as $q) {
      $essayTotalPoints += (int) ($q['max_points'] ?? 10);
    }

    $mcTotalPoints = max(0, $totalPoints - $essayTotalPoints);
    $mcCount = count($mcQuestions);
    $mcPointsEach = $mcCount > 0 ? round($mcTotalPoints / $mcCount, 2) : 0;

    return [
      'total' => $totalPoints,
      'essayTotal' => $essayTotalPoints,
      'essayCount' => count($essayQuestions),
      'mcTotal' => $mcTotalPoints,
      'mcCount' => $mcCount,
      'mcPointsEach' => $mcPointsEach,
      'isOverLimit' => $essayTotalPoints > $totalPoints,
    ];
  }
}
