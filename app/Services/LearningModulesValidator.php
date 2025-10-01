<?php

namespace App\Services;

class LearningModulesValidator
{
    /**
     * Validate hierarchical structure before persisting.
     * Mutates $topics (passed by reference) similarly to old component method (without pruning) and
     * returns an associative array: [ 'errors'=>[], 'errorTopicKeys'=>[], 'errorSectionKeys'=>[], 'errorResourceKeys'=>[], 'errorQuestionKeys'=>[] ]
     */
    public function validate(array &$topics): array
    {
        $errors = [];
        $errorTopicKeys = [];
        $errorSectionKeys = [];
        $errorResourceKeys = [];
        $errorQuestionKeys = [];

        foreach ($topics as $ti => &$topic) {
            $topicTitle = trim($topic['title'] ?? '');
            if (!isset($topic['sections']) || !is_array($topic['sections'])) {
                $topic['sections'] = [];
            }
            $topicHasContent = false;
            $allSectionTitlesEmpty = true;
            foreach ($topic['sections'] as $si => &$section) {
                $sectionTitle = trim($section['title'] ?? '');
                if ($sectionTitle !== '') {
                    $allSectionTitlesEmpty = false;
                }
                if (!isset($section['resources']) || !is_array($section['resources'])) {
                    $section['resources'] = [];
                }
                $resourceCount = count($section['resources']);
                if ($resourceCount > 0) {
                    foreach ($section['resources'] as $ri => $res) {
                        $rtype = $res['type'] ?? '';
                        $rurl = trim($res['url'] ?? '');
                        if (in_array($rtype, ['pdf', 'youtube'])) {
                            if ($rurl === '') {
                                $labelType = $rtype === 'pdf' ? 'PDF' : 'YouTube';
                                $errors[] = "$labelType resource has no file / link (Topic #" . ($ti + 1) . ", Section #" . ($si + 1) . ", Resource #" . ($ri + 1) . ") - remove it (X) or provide a valid " . ($rtype === 'pdf' ? 'upload.' : 'URL.');
                                $errorResourceKeys[] = "t{$ti}-s{$si}-r{$ri}";
                            } elseif ($rtype === 'youtube') {
                                if (!preg_match('/(youtu.be\/|youtube.com\/(watch\?v=|embed\/|shorts\/))/i', $rurl)) {
                                    $errors[] = "YouTube resource link looks invalid (Topic #" . ($ti + 1) . ", Section #" . ($si + 1) . ", Resource #" . ($ri + 1) . ")";
                                    $errorResourceKeys[] = "t{$ti}-s{$si}-r{$ri}";
                                }
                            }
                        }
                    }
                }
                if (!isset($section['quiz']) || !is_array($section['quiz'])) {
                    $section['quiz'] = ['enabled' => false, 'questions' => []];
                }
                $quizEnabled = (bool) ($section['quiz']['enabled'] ?? false);
                if (!isset($section['quiz']['questions']) || !is_array($section['quiz']['questions'])) {
                    $section['quiz']['questions'] = [];
                }
                if ($quizEnabled) {
                    foreach ($section['quiz']['questions'] as $qi => &$question) {
                        $questionKey = "t{$ti}-s{$si}-q{$qi}";
                        $questionHasError = false;
                        $qType = $question['type'] ?? 'multiple';
                        $qText = trim($question['question'] ?? '');
                        if ($qText === '') {
                            $errors[] = "Empty question at Topic #" . ($ti + 1) . " Section #" . ($si + 1) . " (Question #" . ($qi + 1) . ")";
                            $questionHasError = true;
                        }
                        if ($qType === 'multiple') {
                            $options = $question['options'] ?? [];
                            $filtered = [];
                            foreach ($options as $oi => $opt) {
                                $optText = trim((string) $opt);
                                if ($optText !== '') {
                                    $filtered[] = $optText;
                                }
                            }
                            $question['options'] = $filtered;
                            $answerIndex = $question['answer'] ?? null;
                            if ($answerIndex !== null && $answerIndex >= count($filtered)) {
                                $question['answer'] = null;
                                $answerIndex = null;
                            }
                            if (count($filtered) < 2) {
                                $errors[] = "Multiple choice requires at least 2 options (Topic #" . ($ti + 1) . ", Section #" . ($si + 1) . ", Question #" . ($qi + 1) . ")";
                                $questionHasError = true;
                            }
                            if (count($filtered) >= 2 && $answerIndex === null) {
                                $errors[] = "No correct answer selected (Topic #" . ($ti + 1) . ", Section #" . ($si + 1) . ", Question #" . ($qi + 1) . ")";
                                $questionHasError = true;
                            }
                        } elseif ($qType === 'essay') {
                            $question['options'] = [];
                            $question['answer'] = null;
                        } else {
                            $errors[] = "Invalid question type (Topic #" . ($ti + 1) . ", Section #" . ($si + 1) . ", Question #" . ($qi + 1) . ")";
                            $questionHasError = true;
                        }
                        if ($questionHasError) {
                            $errorQuestionKeys[] = $questionKey;
                        }
                    }
                    unset($question);
                } else {
                    $section['quiz']['questions'] = [];
                }
                $hasResources = false;
                foreach ($section['resources'] as $resTmp) {
                    $rtypeTmp = $resTmp['type'] ?? '';
                    $rurlTmp = trim($resTmp['url'] ?? '');
                    if (in_array($rtypeTmp, ['pdf', 'youtube']) && $rurlTmp !== '') {
                        $hasResources = true;
                        break;
                    }
                }
                $hasQuestions = $quizEnabled && count($section['quiz']['questions']) > 0;
                if ($sectionTitle === '' && !$hasResources && !$hasQuestions) {
                    $errors[] = "Empty sub topic (Topic #" . ($ti + 1) . ", Section #" . ($si + 1) . ") - add a title, a resource, or a quiz question.";
                    $errorSectionKeys[] = "t{$ti}-s{$si}";
                } else {
                    if ($sectionTitle !== '' || $hasResources || $hasQuestions) {
                        $topicHasContent = true;
                    }
                }
            }
            unset($section);
            if ($topicTitle === '' && !$topicHasContent) {
                $errors[] = "Empty topic (#" . ($ti + 1) . ") - add a title or at least one non-empty sub topic.";
                $errorTopicKeys[] = "t{$ti}";
            } elseif ($topicTitle === '' && $allSectionTitlesEmpty) {
                $errors[] = "Topic #" . ($ti + 1) . " has no title and all its sub topics have empty titles - provide a topic or sub topic title.";
                $errorTopicKeys[] = "t{$ti}";
                foreach ($topic['sections'] as $si2 => $section2) {
                    $secTitle2 = trim($section2['title'] ?? '');
                    if ($secTitle2 === '') {
                        $errorSectionKeys[] = "t{$ti}-s{$si2}";
                    }
                }
            }
        }
        unset($topic);

        return [
            'errors' => $errors,
            'errorTopicKeys' => $errorTopicKeys,
            'errorSectionKeys' => $errorSectionKeys,
            'errorResourceKeys' => $errorResourceKeys,
            'errorQuestionKeys' => $errorQuestionKeys,
        ];
    }
}
