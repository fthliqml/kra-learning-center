<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\ValidationException;

class TestQuestionsImport implements ToCollection, WithHeadingRow
{
  protected string $type;
  protected array $questions = [];
  protected array $errors = [];
  protected int $rowNumberOffset = 2; // heading row = 1

  public function __construct(string $type = 'pretest')
  {
    $this->type = $type;
  }

  public function collection(Collection $rows)
  {
    foreach ($rows as $index => $row) {
      $excelRow = $index + $this->rowNumberOffset;

      // Skip completely empty rows
      if ($row->filter(fn($v) => trim((string) $v) !== '')->isEmpty()) {
        continue;
      }

      $type = strtolower(trim((string) ($row['type'] ?? 'multiple')));
      $question = trim((string) ($row['question'] ?? ''));
      $optionA = trim((string) ($row['option_a'] ?? ''));
      $optionB = trim((string) ($row['option_b'] ?? ''));
      $optionC = trim((string) ($row['option_c'] ?? ''));
      $optionD = trim((string) ($row['option_d'] ?? ''));
      $optionE = trim((string) ($row['option_e'] ?? ''));
      $correctAnswer = strtoupper(trim((string) ($row['correct_answer'] ?? '')));

      // Validate question type
      if (!in_array($type, ['multiple', 'essay'])) {
        $this->errors[] = "Row {$excelRow}: Invalid Type '{$type}'. Use 'multiple' or 'essay'.";
        continue;
      }

      // Validate question text
      if ($question === '') {
        $this->errors[] = "Row {$excelRow}: Question text is required.";
        continue;
      }

      // Build question array
      $questionData = [
        'id' => Str::uuid()->toString(),
        'type' => $type,
        'question' => $question,
        'options' => [],
        'answer' => null,
        'answer_nonce' => 0,
      ];

      if ($type === 'multiple') {
        // Collect non-empty options
        $options = [];
        $optionLetters = ['A', 'B', 'C', 'D', 'E'];
        $optionValues = [$optionA, $optionB, $optionC, $optionD, $optionE];

        foreach ($optionValues as $idx => $optValue) {
          if ($optValue !== '') {
            $options[] = $optValue;
          }
        }

        // Validate at least 2 options for multiple choice
        if (count($options) < 2) {
          $this->errors[] = "Row {$excelRow}: Multiple choice questions require at least 2 options.";
          continue;
        }

        $questionData['options'] = $options;

        // Validate correct answer
        if ($correctAnswer !== '') {
          $answerIndex = array_search($correctAnswer, $optionLetters);

          if ($answerIndex === false) {
            $this->errors[] = "Row {$excelRow}: Invalid Correct Answer '{$correctAnswer}'. Use A, B, C, D, or E.";
            continue;
          }

          // Check if the answer option exists
          if ($answerIndex >= count($options)) {
            $this->errors[] = "Row {$excelRow}: Correct Answer '{$correctAnswer}' refers to an empty option.";
            continue;
          }

          // Map to the actual index in our options array
          // We need to find which index the letter corresponds to after filtering empty options
          $letterToActualIndex = [];
          $actualIdx = 0;
          foreach ($optionValues as $letterIdx => $optValue) {
            if ($optValue !== '') {
              $letterToActualIndex[$optionLetters[$letterIdx]] = $actualIdx;
              $actualIdx++;
            }
          }

          if (isset($letterToActualIndex[$correctAnswer])) {
            $questionData['answer'] = $letterToActualIndex[$correctAnswer];
          } else {
            $this->errors[] = "Row {$excelRow}: Correct Answer '{$correctAnswer}' refers to an empty option.";
            continue;
          }
        }
      }

      $this->questions[] = $questionData;
    }

    // Throw validation exception if errors found
    if (!empty($this->errors)) {
      throw ValidationException::withMessages([
        'import' => $this->errors,
      ]);
    }
  }

  /**
   * Get the imported questions array.
   */
  public function getQuestions(): array
  {
    return $this->questions;
  }

  /**
   * Get import errors.
   */
  public function getErrors(): array
  {
    return $this->errors;
  }
}
