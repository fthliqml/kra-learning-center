<?php

namespace App\Services;

class SurveyAnswersValidator
{
    /**
     * Validate answers for a survey.
     * - All questions must be answered (non-empty string for essay, non-empty for multiple)
     * - Return array: errors (list of error messages), errorQuestionIndexes (for UI highlight)
     */
    public function validate(array $questions, array $answers): array
    {
        $errors = [];
        $errorQuestionIndexes = [];
        foreach ($questions as $idx => $q) {
            $qid = (string) ($q->id ?? $q['id'] ?? '');
            $ans = $answers[$qid] ?? null;
            $isEmpty = false;
            if (($q->question_type ?? $q['question_type'] ?? '') === 'multiple') {
                $isEmpty = !is_string($ans) || trim($ans) === '';
            } elseif (($q->question_type ?? $q['question_type'] ?? '') === 'essay') {
                $isEmpty = !is_string($ans) || trim($ans) === '';
            } else {
                $isEmpty = true;
            }
            if ($isEmpty) {
                $errors[] = 'Question #' . ($idx + 1) . ' must be answered.';
                $errorQuestionIndexes[] = $idx;
            }
        }
        return [
            'errors' => $errors,
            'errorQuestionIndexes' => $errorQuestionIndexes,
        ];
    }
}
