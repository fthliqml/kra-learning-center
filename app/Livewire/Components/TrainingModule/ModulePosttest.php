<?php

namespace App\Livewire\Components\TrainingModule;

use App\Exports\TestQuestionsTemplateExport;
use App\Imports\TestQuestionsImport;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use App\Services\PretestQuestionsValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

class ModulePosttest extends Component
{
  use Toast, WithFileUploads;

  public array $questions = [];
  public $file;
  public ?int $moduleId = null;

  public bool $hasEverSaved = false;
  public bool $persisted = false;
  public bool $isDirty = false;
  protected string $originalHash = '';
  public array $errorQuestionIndexes = [];

  // Test configuration
  public int $passingScore = 75;
  public ?int $maxAttempts = null;
  public bool $randomizeQuestion = false;
  public bool $showResultImmediately = true;

  protected $listeners = [
    'moduleCreated' => 'onModuleCreated',
  ];

  public function onModuleCreated(int $newModuleId): void
  {
    if (!$this->moduleId) {
      $this->moduleId = $newModuleId;
      $this->hydrateFromModule();
      if (empty($this->questions)) {
        $this->questions = [$this->makeQuestion()];
      }
    }
  }

  public function mount(): void
  {
    if ($this->moduleId) {
      $this->hydrateFromModule();
    }
    if (empty($this->questions)) {
      $this->questions = [$this->makeQuestion()];
    }
  }

  protected function hydrateFromModule(): void
  {
    $existingTest = Test::where('training_module_id', $this->moduleId)
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
    $this->showResultImmediately = $existingTest->show_result_immediately ?? true;

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
      ];
    }

    $this->questions = $loaded;
    $this->snapshot();
  }

  protected function snapshot(): void
  {
    $this->originalHash = md5(json_encode([
      'questions' => $this->questions,
      'passingScore' => $this->passingScore,
      'maxAttempts' => $this->maxAttempts,
      'randomizeQuestion' => $this->randomizeQuestion,
      'showResultImmediately' => $this->showResultImmediately,
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
      'showResultImmediately' => $this->showResultImmediately,
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
        if (!isset($this->questions[$qIndex]['answer_nonce'])) {
          $this->questions[$qIndex]['answer_nonce'] = 0;
        }
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

    if (str_starts_with($prop, 'questions') || in_array($prop, ['passingScore', 'maxAttempts', 'randomizeQuestion', 'showResultImmediately'])) {
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

    if (!isset($q['answer_nonce'])) {
      $q['answer_nonce'] = 0;
    }
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

  public function goBack(): void
  {
    $this->dispatch('setTab', 'pretest');
  }

  public function saveDraft(): void
  {
    $this->errorQuestionIndexes = [];

    if (!$this->moduleId) {
      $this->error(
        'Save Module Information first to create the module before saving the Posttest.',
        timeout: 6000,
        position: 'toast-top toast-center'
      );
      return;
    }

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
      $this->error($htmlMessage, timeout: 10000, position: 'toast-top toast-center');
      return;
    }

    if (empty($this->questions)) {
      $this->error(
        'Cannot save empty posttest. Add at least one question.',
        timeout: 6000,
        position: 'toast-top toast-center'
      );
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

    DB::transaction(function () {
      $test = Test::updateOrCreate(
        [
          'training_module_id' => $this->moduleId,
          'type' => 'posttest',
        ],
        [
          'passing_score' => $this->passingScore,
          'max_attempts' => $this->maxAttempts,
          'randomize_question' => $this->randomizeQuestion,
          'show_result_immediately' => $this->showResultImmediately,
        ]
      );

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
    $this->snapshot();
    $this->success('Posttest saved successfully!', timeout: 4000, position: 'toast-top toast-center');
  }

  public function downloadTemplate()
  {
    return response()->streamDownload(function () {
      echo Excel::raw(new TestQuestionsTemplateExport('posttest'), \Maatwebsite\Excel\Excel::XLSX);
    }, 'module_posttest_template.xlsx');
  }

  public function updatedFile()
  {
    if (!$this->file)
      return;

    try {
      $import = new TestQuestionsImport('posttest');
      Excel::import($import, $this->file->getRealPath());

      $importedQuestions = $import->getQuestions();

      if (empty($importedQuestions)) {
        $this->error('No valid questions found in the uploaded file.', timeout: 6000, position: 'toast-top toast-center');
        $this->reset('file');
        return;
      }

      $this->questions = array_merge($this->questions, $importedQuestions);
      $this->computeDirty();

      $this->success(count($importedQuestions) . ' question(s) imported successfully.', timeout: 4000, position: 'toast-top toast-center');
    } catch (ValidationException $e) {
      $errors = $e->errors()['import'] ?? [];
      $bulletLines = collect($errors)->take(6)->map(fn($err) => '• ' . $err);
      $display = $bulletLines->implode("\n");
      if (count($errors) > 6) {
        $display .= "\n..." . (count($errors) - 6) . " more errors";
      }
      $htmlMessage = "<div style=\"white-space:pre-line; text-align:left\"><strong>Import failed:</strong>\n" . e($display) . '</div>';
      $this->error($htmlMessage, timeout: 10000, position: 'toast-top toast-center');
    } catch (\Exception $e) {
      $this->error('Failed to import file: ' . $e->getMessage(), timeout: 6000, position: 'toast-top toast-center');
    }

    $this->reset('file');
  }

  public function placeholder()
  {
    return view('components.skeletons.pretest-questions');
  }

  public function render()
  {
    return view('components.training-module.module-posttest');
  }
}
