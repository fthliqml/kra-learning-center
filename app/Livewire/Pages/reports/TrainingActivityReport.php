<?php

namespace App\Livewire\Pages\Reports;

use App\Exports\TrainingActivityReportExport;
use App\Models\Training;
use App\Models\TrainingAssessment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

class TrainingActivityReport extends Component
{
    use Toast, WithPagination;

    public $search = '';
    public $dateRange = null;

    public function mount(): void
    {
        // No default date filter - show all data
        $this->dateRange = null;
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'dateRange']);
        $this->resetPage();
    }

    /**
     * Parse date range string into from and to dates
     */
    protected function parseDateRange(): array
    {
        if (!$this->dateRange) {
            return [null, null];
        }

        $dates = explode(' to ', $this->dateRange);
        $from = isset($dates[0]) ? trim($dates[0]) : null;
        $to = isset($dates[1]) ? trim($dates[1]) : $from; // If single date, use same for both

        return [$from, $to];
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'dateRange'])) {
            $this->resetPage();
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-[60px]'],
            ['key' => 'event_code', 'label' => 'Event Code', 'class' => '!text-center w-[120px]'],
            ['key' => 'training_name', 'label' => 'Training Name', 'class' => 'w-[260px]'],
            ['key' => 'group_comp', 'label' => 'Group Comp', 'class' => '!text-center w-[110px]'],
            ['key' => 'type', 'label' => 'Type', 'class' => '!text-center w-[90px]'],
            ['key' => 'instructor', 'label' => 'Instructor', 'class' => 'w-[180px]'],
            ['key' => 'venue', 'label' => 'Venue', 'class' => 'w-[220px]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center w-[110px]'],
            ['key' => 'employee_name', 'label' => 'Name', 'class' => 'w-[180px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center w-[90px]'],
            ['key' => 'period', 'label' => 'Period', 'class' => '!text-center w-[220px]'],
            ['key' => 'duration', 'label' => 'Duration', 'class' => '!text-center w-[140px]'],
            ['key' => 'theory_score', 'label' => 'Theory', 'class' => '!text-center w-[90px]'],
            ['key' => 'practical_score', 'label' => 'Practical', 'class' => '!text-center w-[90px]'],
            ['key' => 'remarks', 'label' => 'Remarks', 'class' => '!text-center w-[110px]'],
            ['key' => 'date_report', 'label' => 'Date Report', 'class' => '!text-center w-[130px]'],
            ['key' => 'certificate', 'label' => 'Certificate', 'class' => '!text-center w-[110px]'],
            ['key' => 'note', 'label' => 'Note', 'class' => 'w-[200px]'],
        ];
    }

    public function getReportsProperty()
    {
        [$dateFrom, $dateTo] = $this->parseDateRange();

        // Get all trainings with status 'done' within date range
        $query = Training::with([
            'sessions.trainer',
            'assessments.employee',
        ])
            ->where('status', 'done')
            ->when($dateFrom, fn($q) => $q->whereDate('end_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('end_date', '<=', $dateTo))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));

        $trainings = $query->orderBy('end_date', 'desc')->get();

        // Flatten data: one row per participant per training
        $reports = collect();
        $no = 1;

        foreach ($trainings as $training) {
            // Get first session for instructor and venue info
            $firstSession = $training->sessions->first();
            $instructor = $firstSession?->trainer?->name ?? ($firstSession?->trainer?->user?->name ?? '-');
            $venue = $firstSession
                ? trim(($firstSession->room_location ?? '') . ' - ' . ($firstSession->room_name ?? ''), ' -')
                : '-';
            if ($venue === '-' || empty(trim($venue, ' -'))) {
                $venue = '-';
            }

            // Calculate duration
            $days = $training->duration;
            $totalHours = $training->sessions->count() > 0
                ? $training->sessions->sum(function ($session) {
                    if ($session->start_time && $session->end_time) {
                        return Carbon::parse($session->start_time)->diffInHours(Carbon::parse($session->end_time));
                    }
                    return 0;
                })
                : ($days * 8); // Default 8 hours per day

            $durationText = $totalHours . ' Hours (' . $days . ' Days)';

            // Period
            $startDate = $training->start_date ? Carbon::parse($training->start_date)->format('d-m-Y') : '-';
            $endDate = $training->end_date ? Carbon::parse($training->end_date)->format('d-m-Y') : '-';
            $period = $startDate . ' - ' . $endDate;

            // For each assessment (participant)
            foreach ($training->assessments as $assessment) {
                $employee = $assessment->employee;

                $reports->push((object) [
                    'id' => $training->id . '-' . ($assessment->employee_id ?? 0),
                    'no' => $no++,
                    'event_code' => 'TRN-' . str_pad($training->id, 5, '0', STR_PAD_LEFT),
                    'training_name' => $training->name ?? '-',
                    'group_comp' => $training->group_comp ?? '-',
                    'type' => ucfirst($training->type ?? '-'),
                    'instructor' => $instructor,
                    'venue' => $venue ?: '-',
                    'nrp' => $employee->nrp ?? '-',
                    'employee_name' => $employee->name ?? '-',
                    'section' => $employee->section ?? '-',
                    'period' => $period,
                    'duration' => $durationText,
                    'theory_score' => $assessment->posttest_score !== null ? number_format($assessment->posttest_score, 1) : '-',
                    'practical_score' => $assessment->practical_score !== null ? number_format($assessment->practical_score, 1) : '-',
                    'theory_raw' => $assessment->posttest_score,
                    'practical_raw' => $assessment->practical_score,
                    'remarks' => $assessment->status ?? 'failed',
                    'date_report' => $training->end_date ? Carbon::parse($training->end_date)->format('d-m-Y') : '-',
                    'certificate_url' => $assessment->status === 'passed' ? route('certificate.training', ['training' => $training->id, 'employee' => $assessment->employee_id]) : null,
                    'note' => $assessment->notes ?? '-',
                ]);
            }
        }

        return $reports;
    }

    public function reports()
    {
        $reportsData = $this->reports;
        $perPage = 10;
        $currentPage = $this->getPage();
        $total = $reportsData->count();

        // Paginate manually
        $items = $reportsData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );
    }

    public function export()
    {
        [$dateFrom, $dateTo] = $this->parseDateRange();

        return Excel::download(
            new TrainingActivityReportExport($dateFrom, $dateTo, $this->search),
            'training_activity_report_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function render()
    {
        return view('pages.reports.training-activity-report', [
            'reports' => $this->reports(),
            'headers' => $this->headers(),
        ]);
    }
}
