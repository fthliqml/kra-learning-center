<?php

namespace App\Livewire\Components\EditCourse;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Course;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use App\Exports\TestQuestionsTemplateExport;
use App\Imports\TestQuestionsImport;
use App\Services\PostTestQuestionsValidator;
use Illuminate\Validation\ValidationException;

class PostTestQuestions extends Component
{
  use Toast, WithFileUploads;

  public array $questions = [];
  public $file; // For file upload
  public ?int $courseId = null;
  public bool $hasEverSaved = false;
  public bool $persisted = false;
  public bool $isDirty = false; // track unsaved changes
  protected string $originalHash = ''; // hash of saved state
  public array $errorQuestionIndexes = [];

  // Test configuration
  public int $passingScore = 75;
  public ?int $maxAttempts = null;
  public bool $randomizeQuestion = false;

  // Listen for new course draft creation so we can attach and persist
  protected $listeners = [
    'courseCreated' => 'onCourseCreated',
  ];

  public function onCourseCreated(int $newCourseId): void
  {
    if (!$this->courseId) {
      $this->courseId = $newCourseId;
      $this->hydrateFromCourse();
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

  protected function hydrateFromCourse(): void
  {
    $existingTest = Test::where('course_id', $this->courseId)
      ->where('type', 'posttest')
      ->first();
    if (!$existingTest) {
      $this->questions = [];
      return;
    }

    // Load test config
    $this->passingScore = $existingTest->passing_score ?? 75;
    $this->maxAttempts = $existingTest->max_attempts;
    $this->randomizeQuestion = $existingTest->randomize_question ?? false;

    $existingTest->load(['questions.options']);
    $loaded = [];
    foreach ($existingTest->questions->sortBy('order')->values() as $qModel) {
      $options = $qModel->options->sortBy('order')->values();
      $opts = $options->map(fn($o) => $o->text)->all();
      $answerIndex = null;
      foreach ($options as $idx => $opt) {
        if ($opt->is_correct) {
          $answerIndex = $idx;
          break;
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
    if (!empty($loaded)) {
      $this->hasEverSaved = true;
      $this->persisted = true;
    }
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
      'answer' => null,
      'answer_nonce' => 0,
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
        if (!isset($this->questions[$qIndex]['answer_nonce']))
          $this->questions[$qIndex]['answer_nonce'] = 0;
        $ans = &$this->questions[$qIndex]['answer'];
        if ($ans !== null) {
          if ($ans == $oIndex) {
            $ans = null;
            $this->questions[$qIndex]['answer_nonce']++;
          } elseif ($ans > $oIndex) {
            $ans -= 1;
          }
        }
      }
      $this->computeDirty();
    }
  }

  public function updated($prop): void
  {
    // Handle question type changes
    if (!is_array($prop) && preg_match('/^questions\\.(\\d+)\\.type$/', $prop, $m)) {
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
    $new = ($current === $oIndex) ? null : $oIndex;
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
    if (empty($orderedIds))
      return;
    $current = array_map(fn($q) => $q['id'], $this->questions);
    if ($current === $orderedIds)
      return;
    $lookup = [];
    foreach ($this->questions as $q) {
      $lookup[$q['id']] = $q;
    }
    $new = [];
    foreach ($orderedIds as $id) {
      if (isset($lookup[$id])) {
        $item = $lookup[$id];
        if ($item['type'] === 'essay')
          $item['options'] = [];
        elseif ($item['type'] === 'multiple' && empty($item['options']))
          $item['options'] = [''];
        $new[] = $item;
        unset($lookup[$id]);
      }
    }
    foreach ($lookup as $left) {
      if ($left['type'] === 'essay')
        $left['options'] = [];
      elseif ($left['type'] === 'multiple' && empty($left['options']))
        $left['options'] = [''];
      $new[] = $left;
    }
    $this->questions = $new;
    $this->computeDirty();
  }

  public function goBack(): void
  {
    $this->dispatch('setTab', 'learning-module');
  }

  public function goNext(): void
  {
    $this->dispatch('setTab', 'course-info');
  }

  public function finish(): void
  {
    if ($this->courseId) {
      $course = Course::find($this->courseId);
      if ($course && $course->isComplete()) {
        $course->update(['status' => 'inactive']);
        $this->success('Course completed and ready to be assigned!', timeout: 4000, position: 'toast-top toast-center');
      } elseif ($course && $course->status === 'draft') {
        $this->warning('Course saved as draft. Complete all sections to make it ready.', timeout: 5000, position: 'toast-top toast-center');
      }
    }

    $this->redirectRoute('courses-management.index', navigate: true);
  }

  public function saveDraft(): void
  {
    $this->errorQuestionIndexes = [];
    if (!$this->courseId) {
      $this->error('Please save the Course Info tab first to create the course before adding post test questions.', timeout: 6000, position: 'toast-top toast-center');
      return;
    }
    $validator = new PostTestQuestionsValidator();
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
      $this->error($htmlMessage, timeout: 10000, position: 'toast-top toast-center');
      return;
    }
    if (empty($this->questions)) {
      $this->error('Cannot save empty post test. Add at least one question.', timeout: 6000, position: 'toast-top toast-center');
      return;
    }
    foreach ($this->questions as &$q) {
      $q['question'] = trim($q['question'] ?? '');
      if (($q['type'] ?? '') === 'multiple') {
        $q['options'] = array_map(fn($o) => trim($o), $q['options'] ?? []);
      } else {
        $q['options'] = [];
      }
    }
    unset($q);

    // IMPORTANT: If there are already attempts for this posttest, do NOT delete/recreate questions.
    // Wiping questions cascades and deletes historical answers.
    $existingTest = Test::where('course_id', $this->courseId)
      ->where('type', 'posttest')
      ->first();
    if ($existingTest && TestAttempt::where('test_id', $existingTest->id)->exists()) {
      $existingTest->load(['questions.options']);

      $dbNormalized = $existingTest->questions->sortBy('order')->values()->map(function ($qModel) {
        $options = $qModel->options->sortBy('order')->values();
        $opts = $options->map(fn($o) => (string) ($o->text ?? ''))->all();
        $answerIndex = null;
        foreach ($options as $idx => $opt) {
          if ($opt->is_correct) {
            $answerIndex = $idx;
            break;
          }
        }
        return [
          'type' => $qModel->question_type ?? 'multiple',
          'question' => (string) ($qModel->text ?? ''),
          'options' => ($qModel->question_type === 'multiple') ? $opts : [],
          'answer' => ($qModel->question_type === 'multiple') ? $answerIndex : null,
          'max_points' => ($qModel->question_type === 'essay') ? ($qModel->max_points ?? 10) : null,
        ];
      })->all();

      $draftNormalized = collect($this->questions)->values()->map(function ($q) {
        $type = in_array($q['type'] ?? '', ['multiple', 'essay']) ? $q['type'] : 'multiple';
        $opts = [];
        if ($type === 'multiple') {
          $opts = array_values(array_map(fn($o) => (string) $o, $q['options'] ?? []));
        }
        return [
          'type' => $type,
          'question' => (string) ($q['question'] ?? ''),
          'options' => $opts,
          'answer' => ($type === 'multiple') ? ($q['answer'] ?? null) : null,
          'max_points' => ($type === 'essay') ? ((int) ($q['max_points'] ?? 10)) : null,
        ];
      })->all();

      if ($dbNormalized !== $draftNormalized) {
        $this->error(
          'Post-Test sudah pernah dikerjakan. Untuk menjaga histori jawaban, pertanyaan/opsi tidak boleh diubah. Anda masih bisa mengubah Passing Score, Max Attempts, dan Randomize.',
          timeout: 9000,
          position: 'toast-top toast-center'
        );
        return;
      }
    }

    DB::transaction(function () {
      $test = Test::updateOrCreate(
        ['course_id' => $this->courseId, 'type' => 'posttest'],
        [
          'passing_score' => $this->passingScore,
          'max_attempts' => $this->maxAttempts,
          'randomize_question' => $this->randomizeQuestion,
          'show_result_immediately' => true,
        ]
      );

      // If attempts exist, do not modify questions/options (would delete answers via FK cascade)
      $hasAttempts = TestAttempt::where('test_id', $test->id)->exists();
      if ($hasAttempts) {
        return;
      }

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
        $questionType = in_array(($q['type'] ?? ''), ['multiple', 'essay']) ? $q['type'] : 'multiple';
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
              continue;
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
    $this->errorQuestionIndexes = [];
    $this->snapshot(); // Take snapshot after successful save
    $this->success('Post test questions saved successfully', timeout: 4000, position: 'toast-top toast-center');
  }

  /**
   * Download template Excel file for importing questions.
   */
  public function downloadTemplate()
  {
    return response()->streamDownload(function () {
      echo Excel::raw(new TestQuestionsTemplateExport('posttest'), \Maatwebsite\Excel\Excel::XLSX);
    }, 'posttest_questions_template.xlsx');
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
      $import = new TestQuestionsImport('posttest');
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
    return view('components.skeletons.post-test-questions');
  }

  public function render()
  {
    // Calculate points distribution for display
    $pointsInfo = $this->calculatePointsDistribution();

    return view('components.edit-course.post-test-questions', [
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
