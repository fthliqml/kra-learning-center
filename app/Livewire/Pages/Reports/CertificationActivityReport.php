<?php

namespace App\Livewire\Pages\Reports;

use App\Exports\CertificationActivityReportExport;
use App\Models\CertificationParticipant;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;
use App\Models\Certification;

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
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center w-[110px]'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-[220px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center w-[110px]'],
            ['key' => 'theory_score', 'label' => 'Theory Score', 'class' => '!text-center w-[110px]'],
            ['key' => 'practical_score', 'label' => 'Practical Score', 'class' => '!text-center w-[130px]'],
            ['key' => 'remarks', 'label' => 'Remarks', 'class' => '!text-center w-[100px]'],
            ['key' => 'note', 'label' => 'Note', 'class' => 'w-[200px]'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-center w-[130px]'],
        ];
    }

    public function reports()
    {
        [$dateFrom, $dateTo] = $this->parseDateRange();

        $query = CertificationParticipant::query()
            ->with([
                'certification.certificationModule',
                'employee',
                'scores.session',
            ])
            ->whereHas('certification', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'completed')
                    ->when($dateFrom, fn($query) => $query->whereDate('approved_at', '>=', $dateFrom))
                    ->when($dateTo, fn($query) => $query->whereDate('approved_at', '<=', $dateTo));
            })
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';

                $q->where(function ($inner) use ($term) {
                    $inner
                        ->whereHas('employee', function ($query) use ($term) {
                            $query->where('name', 'like', $term)->orWhere('nrp', 'like', $term);
                        })
                        ->orWhereHas('certification', fn($query) => $query->where('name', 'like', $term))
                        ->orWhereHas('certification.certificationModule', fn($query) => $query->where('module_title', 'like', $term));
                });
            })
            // Ensure consistent sorting (fixes jumbled numbering)
            ->orderByDesc(
                Certification::select('approved_at')
                    ->whereColumn('certifications.id', 'certification_participants.certification_id')
                    ->limit(1)
            )
            ->orderByDesc('certification_participants.id');

        $paginator = $query->paginate(10);
        $startNo = (int) ($paginator->firstItem() ?? 0);

        $paginator->setCollection(
            $paginator->getCollection()->values()->map(function ($participant, $index) use ($startNo) {
                $certification = $participant->certification;
                $module = $certification?->certificationModule;
                $employee = $participant->employee;

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

                $theoryPassingScore = $module?->theory_passing_score ?? 70;
                $practicalPassingScore = $module?->practical_passing_score ?? 70;
                $remarks = $participant->final_status ?? 'pending';

                $completionDate = $certification?->approved_at
                    ? Carbon::parse($certification->approved_at)->format('d-m-Y')
                    : '-';

                return (object) [
                    'id' => $participant->id,
                    'no' => $startNo + $index,
                    'nrp' => $employee?->nrp ?? '-',
                    'name' => $employee?->name ?? '-',
                    'section' => $employee?->section ?? '-',
                    'theory_score' => $theoryScore !== null ? number_format($theoryScore, 1) : '-',
                    'practical_score' => $practicalScore !== null ? number_format($practicalScore, 1) : '-',
                    'theory_raw' => $theoryScore,
                    'practical_raw' => $practicalScore,
                    'theory_passing' => $theoryPassingScore,
                    'practical_passing' => $practicalPassingScore,
                    'remarks' => $remarks,
                    'note' => '-',
                    'date' => $completionDate,
                ];
            })
        );

        return $paginator;
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
