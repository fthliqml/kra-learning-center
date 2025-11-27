<?php

namespace App\Livewire\Pages\Reports;

use App\Exports\CertificationActivityReportExport;
use App\Models\Certification;
use App\Models\CertificationParticipant;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

class CertificationActivityReport extends Component
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
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-[50px]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center w-[100px]'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-[180px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center w-[100px]'],
            ['key' => 'competency', 'label' => 'Competency', 'class' => 'w-[200px]'],
            ['key' => 'theory_score', 'label' => 'Theory Score', 'class' => '!text-center w-[100px]'],
            ['key' => 'practical_score', 'label' => 'Practical Score', 'class' => '!text-center w-[120px]'],
            ['key' => 'remarks', 'label' => 'Remarks', 'class' => '!text-center w-[100px]'],
            ['key' => 'earned_point', 'label' => 'Earned Point', 'class' => '!text-center w-[100px]'],
            ['key' => 'total_point', 'label' => 'Total Point', 'class' => '!text-center w-[100px]'],
            ['key' => 'note', 'label' => 'Note', 'class' => 'w-[150px]'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-center w-[120px]'],
        ];
    }

    public function getReportsProperty()
    {
        [$dateFrom, $dateTo] = $this->parseDateRange();

        // Get all certification participants with completed certifications
        $query = CertificationParticipant::with([
            'certification.certificationModule',
            'certification.sessions',
            'employee',
            'scores.session',
        ])
            ->whereHas('certification', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'completed')
                    ->when($dateFrom, fn($query) => $query->whereDate('approved_at', '>=', $dateFrom))
                    ->when($dateTo, fn($query) => $query->whereDate('approved_at', '<=', $dateTo));
            })
            ->when($this->search, function ($q) {
                $q->whereHas('employee', fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('certification', fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('certification.certificationModule', fn($query) => $query->where('competency', 'like', '%' . $this->search . '%'));
            });

        $participants = $query->get();

        // Build report data
        $reports = collect();
        $no = 1;

        foreach ($participants as $participant) {
            $certification = $participant->certification;
            $module = $certification?->certificationModule;
            $employee = $participant->employee;

            // Get theory and practical scores from certification_scores
            $theoryScore = null;
            $practicalScore = null;

            foreach ($participant->scores as $score) {
                $sessionType = $score->session?->type;
                if ($sessionType === 'theory') {
                    $theoryScore = $score->score;
                } elseif ($sessionType === 'practical') {
                    $practicalScore = $score->score;
                }
            }

            // Get passing scores from module
            $theoryPassingScore = $module?->theory_passing_score ?? 70;
            $practicalPassingScore = $module?->practical_passing_score ?? 70;

            // Determine remarks based on final_status or calculate from scores
            $remarks = $participant->final_status ?? 'pending';
            if ($remarks === 'pending') {
                // Calculate based on scores if final_status not set
                $theoryPassed = $theoryScore !== null && $theoryScore >= $theoryPassingScore;
                $practicalPassed = $practicalScore !== null && $practicalScore >= $practicalPassingScore;
                $remarks = ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';
            }

            // Earned points from participant or module
            $earnedPoint = $participant->earned_points ?? 0;
            $totalPoint = $module?->points_per_module ?? 0;

            // Completion date - use approved_at from certification
            $completionDate = $certification?->approved_at
                ? Carbon::parse($certification->approved_at)->format('d-m-Y')
                : '-';

            $reports->push((object) [
                'id' => $participant->id,
                'no' => $no++,
                'nrp' => $employee?->NRP ?? '-',
                'name' => $employee?->name ?? '-',
                'section' => $employee?->section ?? '-',
                'competency' => $module?->competency ?? '-',
                'theory_score' => $theoryScore !== null ? number_format($theoryScore, 1) : '-',
                'practical_score' => $practicalScore !== null ? number_format($practicalScore, 1) : '-',
                'theory_raw' => $theoryScore,
                'practical_raw' => $practicalScore,
                'theory_passing' => $theoryPassingScore,
                'practical_passing' => $practicalPassingScore,
                'remarks' => $remarks,
                'earned_point' => $earnedPoint,
                'total_point' => $totalPoint,
                'note' => '-', // No note field in certification tables, can be customized
                'date' => $completionDate,
            ]);
        }

        // Sort by date descending
        return $reports->sortByDesc('date')->values();
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
            new CertificationActivityReportExport($dateFrom, $dateTo, $this->search),
            'certification_activity_report_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function render()
    {
        return view('pages.reports.certification-activity-report', [
            'reports' => $this->reports(),
            'headers' => $this->headers(),
        ]);
    }
}
