<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SurveyInstructorAssessmentMultiSurveyExport implements WithMultipleSheets
{
    /** @var array<int> */
    protected array $surveyIds;

    /**
     * @param array<int> $surveyIds
     */
    public function __construct(array $surveyIds)
    {
        $this->surveyIds = array_values(array_unique(array_filter(array_map('intval', $surveyIds))));
    }

    public function sheets(): array
    {
        return array_map(fn(int $id) => new SurveyInstructorAssessmentSheetExport($id), $this->surveyIds);
    }
}
