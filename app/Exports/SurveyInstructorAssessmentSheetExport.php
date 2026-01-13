<?php

namespace App\Exports;

use App\Models\TrainingSurvey;
use Maatwebsite\Excel\Concerns\WithTitle;

class SurveyInstructorAssessmentSheetExport extends SurveyInstructorAssessmentExport implements WithTitle
{
    public function title(): string
    {
        $survey = TrainingSurvey::with('training')->find($this->surveyId);

        $base = null;
        if ($survey && $survey->training && !empty($survey->training->name)) {
            $base = (string) $survey->training->name;
        }
        if (!$base) {
            $base = 'Survey ' . (int) $this->surveyId;
        }

        // Excel sheet title rules: <= 31 chars, cannot contain: : \ / ? * [ ]
        $base = preg_replace('/[:\\\/\?\*\[\]]/', ' ', $base);
        $base = preg_replace('/\s+/', ' ', trim((string) $base));

        if ($base === '') {
            $base = 'Survey ' . (int) $this->surveyId;
        }

        return mb_substr($base, 0, 31);
    }
}
