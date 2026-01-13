<?php

namespace App\Exports;

use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use App\Models\TrainingSurvey;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveyInstructorAssessmentExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(
        protected int $surveyId,
    ) {}

    public function headings(): array
    {
        $survey = TrainingSurvey::with(['questions' => fn($q) => $q->orderBy('order')->orderBy('id')])
            ->find($this->surveyId);

        $questionHeadings = [];
        if ($survey) {
            $questionHeadings = $survey->questions
                ->sortBy(fn($q) => (int) ($q->order ?? 0))
                ->values()
                ->map(function ($q) {
                    $order = (int) ($q->order ?? 0);
                    $text = (string) ($q->text ?? '');
                    $text = preg_replace('/\s+/', ' ', trim($text));
                    return 'Q' . ($order > 0 ? $order : $q->id) . ' - ' . $text;
                })
                ->all();
        }

        return array_merge([
            'No',
            'Survey Level',
            'Training Name',
            'Training Start Date',
            'Training End Date',
            'Trainers',
            'Respondent NRP',
            'Respondent Name',
            'Respondent Section',
            'Respondent Department',
            'Respondent Position',
            'Response Status',
            'Submitted At',
        ], $questionHeadings);
    }

    public function collection(): Collection
    {
        $survey = TrainingSurvey::with([
            'training.sessions.trainer.user',
            'training.assessments.employee',
            'questions.options',
        ])->findOrFail($this->surveyId);

        $level = (int) ($survey->level ?? 0);

        // Only Survey 1 and 3 are expected for instructor assessment export.
        // The caller should already gate this, but keep it safe at export level too.
        if (!in_array($level, [1, 3], true)) {
            return collect();
        }

        $training = $survey->training;

        $trainers = '';
        if ($training && $training->relationLoaded('sessions')) {
            $trainers = $training->sessions
                ->map(fn($s) => $s?->trainer_display_name)
                ->filter()
                ->unique()
                ->values()
                ->implode(', ');
        }

        $questions = $survey->questions
            ->sortBy(fn($q) => (int) ($q->order ?? 0))
            ->values();

        // Determine expected respondents:
        // - Level 1: training participants (assessments.employee)
        // - Level 3: area approvers computed from each participant
        $expectedUsers = $this->resolveExpectedRespondents($survey);

        $responses = SurveyResponse::query()
            ->with('employee')
            ->where('survey_id', $survey->id)
            ->get()
            ->keyBy(fn($r) => (int) ($r->employee_id ?? 0));

        $responseIds = $responses->values()->pluck('id')->filter()->values();
        $answersByResponseId = collect();
        if ($responseIds->isNotEmpty()) {
            $answersByResponseId = SurveyAnswer::query()
                ->with(['selectedOption'])
                ->whereIn('response_id', $responseIds->all())
                ->get()
                ->groupBy('response_id')
                ->map(function (Collection $rows) {
                    $map = [];
                    foreach ($rows as $row) {
                        $qid = (int) ($row->question_id ?? 0);
                        if ($qid <= 0) {
                            continue;
                        }
                        $value = null;
                        if (!empty($row->selected_option_id)) {
                            $value = (string) ($row->selectedOption?->text ?? '');
                        } elseif ($row->essay_answer !== null) {
                            $value = (string) $row->essay_answer;
                        }
                        $map[$qid] = $value;
                    }
                    return $map;
                });
        }

        $rows = [];
        $i = 1;

        foreach ($expectedUsers as $user) {
            $userId = (int) ($user?->id ?? 0);
            $resp = $userId > 0 ? $responses->get($userId) : null;

            $respStatus = $resp && $resp->is_completed ? 'Filled' : 'Not Filled';
            $submittedAt = $resp?->submitted_at?->format('Y-m-d H:i');

            $answerMap = $resp ? ($answersByResponseId->get($resp->id, []) ?? []) : [];

            $base = [
                $i,
                $level,
                $training?->name ?? '-',
                $training?->start_date?->format('Y-m-d') ?? '-',
                $training?->end_date?->format('Y-m-d') ?? '-',
                $trainers,
                $user?->nrp ?? '-',
                $user?->name ?? '-',
                $user?->section ?? '-',
                $user?->department ?? '-',
                $user?->position ?? '-',
                $respStatus,
                $submittedAt ?? '-',
            ];

            $questionAnswers = [];
            foreach ($questions as $q) {
                $qid = (int) ($q->id ?? 0);
                $val = $answerMap[$qid] ?? '';
                $questionAnswers[] = is_string($val) ? $val : '';
            }

            $rows[] = array_merge($base, $questionAnswers);
            $i++;
        }

        return collect($rows);
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveExpectedRespondents(TrainingSurvey $survey): Collection
    {
        $level = (int) ($survey->level ?? 0);
        $training = $survey->training;

        if (!$training) {
            return collect();
        }

        $participants = collect();
        if ($training->relationLoaded('assessments')) {
            $participants = $training->assessments
                ->map(fn($a) => $a?->employee)
                ->filter(fn($u) => $u instanceof User)
                ->values();
        } else {
            $participants = $training->assessments()->with('employee')->get()
                ->map(fn($a) => $a?->employee)
                ->filter(fn($u) => $u instanceof User)
                ->values();
        }

        if ($level === 1) {
            return $participants->sortBy(fn(User $u) => strtolower($u->name ?? ''))->values();
        }

        if ($level !== 3) {
            return collect();
        }

        $approverIds = collect();
        foreach ($participants as $participant) {
            foreach ($this->resolveApproversForParticipant($participant) as $approverId) {
                $approverIds->push($approverId);
            }
        }

        $approverIds = $approverIds->filter()->unique()->values();
        if ($approverIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $approverIds->all())
            ->get(['id', 'nrp', 'name', 'section', 'department', 'position'])
            ->sortBy(fn(User $u) => strtolower($u->name ?? ''))
            ->values();
    }

    /**
     * Resolve the expected level-3 approver(s) for a participant.
     * Priority by participant area: SPV -> Section Head -> Dept Head.
     * If participant is already a supervisor/section head, route to the next level.
     *
     * @return array<int>
     */
    private function resolveApproversForParticipant(User $participant): array
    {
        $position = strtolower(trim((string) ($participant->position ?? '')));
        $section = (string) ($participant->section ?? '');
        $department = (string) ($participant->department ?? '');

        // Participant is supervisor -> Section Head -> Dept Head
        if ($position === 'supervisor') {
            $sectionHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['section_head'])
                ->when($section !== '', fn($q) => $q->where('section', $section))
                ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
                ->first();
            if ($sectionHead) {
                return [(int) $sectionHead->id];
            }

            $deptHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
                ->when($department !== '', fn($q) => $q->where('department', $department))
                ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
                ->first();
            return $deptHead ? [(int) $deptHead->id] : [];
        }

        // Participant is section head -> Dept Head
        if ($position === 'section_head') {
            $deptHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
                ->when($department !== '', fn($q) => $q->where('department', $department))
                ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
                ->first();
            return $deptHead ? [(int) $deptHead->id] : [];
        }

        // Default participant: SPV -> Section Head -> Dept Head (by area)
        $spv = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
            ->when($section !== '', fn($q) => $q->where('section', $section))
            ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
            ->first();
        if ($spv) {
            return [(int) $spv->id];
        }

        $sectionHead = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['section_head'])
            ->when($section !== '', fn($q) => $q->where('section', $section))
            ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
            ->first();
        if ($sectionHead) {
            return [(int) $sectionHead->id];
        }

        $deptHead = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
            ->when($department !== '', fn($q) => $q->where('department', $department))
            ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
            ->first();

        return $deptHead ? [(int) $deptHead->id] : [];
    }

    public function styles(Worksheet $sheet)
    {
        $headingsCount = count($this->headings());
        $lastCol = Coordinate::stringFromColumnIndex(max(1, $headingsCount));

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D1E8FF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $widths = [
            'A' => 6,
            'B' => 12,
            'C' => 34,
            'D' => 16,
            'E' => 16,
            'F' => 34,
            'G' => 12,
            'H' => 26,
            'I' => 18,
            'J' => 22,
            'K' => 18,
            'L' => 14,
            'M' => 20,
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Question columns (starting from N) => wider
        for ($idx = 14; $idx <= $headingsCount; $idx++) {
            $col = Coordinate::stringFromColumnIndex($idx);
            $sheet->getColumnDimension($col)->setWidth(40);
        }

        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }
}
