<?php

namespace App\Services;

class SurveyQuestionsValidator
{
    /**
     * Validate the survey questions structure.
     * Rules:
     *  - Must have at least one question
     *  - Each question must have non-empty question text
     *  - Type must be either 'multiple' or 'essay'
     *  - Essay questions: options array must be empty
     *  - Multiple choice questions: require at least 2 non-empty options
     *  - Multiple choice questions: no completely duplicate option texts (case-insensitive)
     * Returns array with:
     *  - errors: list of human-readable error messages
     *  - errorQuestionIndexes: list of integer indexes referencing invalid questions (for UI highlight)
     */
    public function validate(array $questions): array
    {
        $errors = [];
        $errorQuestionIndexes = [];

        if (empty($questions)) {
            $errors[] = 'At least one question is required.';
            return [
                'errors' => $errors,
                'errorQuestionIndexes' => $errorQuestionIndexes,
            ];
        }

        foreach ($questions as $i => $q) {
            $type = $q['question_type'] ?? 'multiple';
            $questionText = trim($q['text'] ?? '');
            if ($questionText === '') {
                $errors[] = 'Question #' . ($i + 1) . ' text cannot be empty.';
                $errorQuestionIndexes[] = $i;
            }
            if (!in_array($type, ['multiple', 'essay'], true)) {
                $errors[] = 'Question #' . ($i + 1) . ' has invalid type.';
                $errorQuestionIndexes[] = $i;
            }
            if ($type === 'essay') {
                // Should have no options; if any non-empty option present flag error
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
                    // Duplicate detection (case-insensitive)
                    $lower = array_map(fn($o) => mb_strtolower(trim($o)), $nonEmpty);
                    $duplicates = $this->findDuplicates($lower);
                    if (!empty($duplicates)) {
                        $errors[] = 'Multiple choice question #' . ($i + 1) . ' has duplicate option texts.';
                        $errorQuestionIndexes[] = $i;
                    }
                }
            }
        }

        // Deduplicate question indexes
        $errorQuestionIndexes = array_values(array_unique($errorQuestionIndexes));

        return [
            'errors' => $errors,
            'errorQuestionIndexes' => $errorQuestionIndexes,
        ];
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
