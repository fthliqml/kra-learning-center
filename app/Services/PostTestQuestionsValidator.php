<?php

namespace App\Services;

class PostTestQuestionsValidator
{
  /**
   * Validate the posttest questions structure.
   * Same rules as pretest:
   *  - At least one question
   *  - Non-empty text
   *  - Type in ['multiple','essay']
   *  - Essay: no options
   *  - Multiple: >=2 non-empty options
   *  - Multiple: no duplicate option texts (case-insensitive)
   *  - Multiple: must have a selected correct answer referencing an existing non-empty option
   */
  public function validate(array $questions): array
  {
    $errors = [];
    $errorQuestionIndexes = [];

    if (empty($questions)) {
      $errors[] = 'At least one question is required.';
      return ['errors' => $errors, 'errorQuestionIndexes' => $errorQuestionIndexes];
    }

    foreach ($questions as $i => $q) {
      $type = $q['type'] ?? 'multiple';
      $questionText = trim($q['question'] ?? '');
      if ($questionText === '') {
        $errors[] = 'Question #' . ($i + 1) . ' text cannot be empty.';
        $errorQuestionIndexes[] = $i;
      }
      if (!in_array($type, ['multiple', 'essay'], true)) {
        $errors[] = 'Question #' . ($i + 1) . ' has invalid type.';
        $errorQuestionIndexes[] = $i;
      }
      if ($type === 'essay') {
        $opts = $q['options'] ?? [];
        $nonEmpty = array_filter($opts, fn($o) => trim($o) !== '');
        if (count($nonEmpty) > 0) {
          $errors[] = 'Essay question #' . ($i + 1) . ' must not have options.';
          $errorQuestionIndexes[] = $i;
        }
      } elseif ($type === 'multiple') {
        $opts = $q['options'] ?? [];
        $nonEmpty = array_values(array_filter($opts, fn($o) => trim($o) !== ''));
        if (count($nonEmpty) < 2) {
          $errors[] = 'Multiple choice question #' . ($i + 1) . ' must have at least 2 options.';
          $errorQuestionIndexes[] = $i;
        } else {
          $lower = array_map(fn($o) => mb_strtolower(trim($o)), $nonEmpty);
          $duplicates = $this->findDuplicates($lower);
          if (!empty($duplicates)) {
            $errors[] = 'Multiple choice question #' . ($i + 1) . ' has duplicate option texts.';
            $errorQuestionIndexes[] = $i;
          }
          $answer = $q['answer'] ?? null;
          if ($answer === null) {
            $errors[] = 'Multiple choice question #' . ($i + 1) . ' requires a correct answer selection.';
            $errorQuestionIndexes[] = $i;
          } else {
            $mapped = [];
            foreach ($opts as $origIdx => $txt) {
              if (trim($txt) !== '')
                $mapped[] = $origIdx;
            }
            if (!in_array($answer, $mapped, true)) {
              $errors[] = 'Multiple choice question #' . ($i + 1) . ' selected answer is invalid after changes.';
              $errorQuestionIndexes[] = $i;
            }
          }
        }
      }
    }

    $errorQuestionIndexes = array_values(array_unique($errorQuestionIndexes));
    return ['errors' => $errors, 'errorQuestionIndexes' => $errorQuestionIndexes];
  }

  private function findDuplicates(array $values): array
  {
    $counts = [];
    foreach ($values as $v) {
      $counts[$v] = ($counts[$v] ?? 0) + 1;
    }
    return array_keys(array_filter($counts, fn($c) => $c > 1));
  }
}
