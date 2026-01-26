<?php

namespace App\Livewire\Pages\Reports;

use App\Exports\TrainingActivityReportExport;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

class TrainingActivityReport extends Component
{
    use Toast, WithPagination;

    public $search = '';
    public $dateRange = null;
    public $filterDepartment = '';
    public $filterSection = '';

    public function mount(): void
    {
        // No default date filter - show all data
        $this->dateRange = null;
    }

    /**
     * Check if current user has full access to all training data
     * Full access: admin, instructor, certificator, multimedia roles, 
     * section_head with section "LID", or division_head with LID division
     */
    protected function hasFullAccess(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Admin, Instructor, Certificator, Multimedia roles have full access
        if ($user->hasAnyRole(['admin', 'instructor', 'certificator', 'multimedia'])) {
            return true;
        }

        // Section head of LID section has full access
        if ($user->hasPosition('section_head') && strtoupper($user->section) === 'LID') {
            return true;
        }

        // Division head of LID division (Human Capital, Finance & General Support) has full access
        if ($user->hasPosition('division_head')) {
            $lidDivision = 'human capital, finance & general support';
            if (strtolower(trim($user->division ?? '')) === $lidDivision) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the access filter type and value for current user
     * Based on menu.php: section_head, department_head, division_head positions and specific roles can access
     * Returns: ['type' => 'full'|'section'|'department'|'division'|'none', 'value' => string|null]
     */
    protected function getAccessFilter(): array
    {
        $user = Auth::user();

        if (!$user) {
            return ['type' => 'none', 'value' => null];
        }

        // Full access for admin, instructor, certificator, multimedia, section_head LID, or division_head LID
        if ($this->hasFullAccess()) {
            return ['type' => 'full', 'value' => null];
        }

        // Division head (non-LID) - filter by their division
        if ($user->hasPosition('division_head')) {
            return ['type' => 'division', 'value' => $user->division];
        }

        // Section head (non-LID) - filter by their section
        if ($user->hasPosition('section_head')) {
            return ['type' => 'section', 'value' => $user->section];
        }

        // Department head - filter by their department
        if ($user->hasPosition('department_head')) {
            return ['type' => 'department', 'value' => $user->department];
        }

        // No access for other positions (employee, supervisor, etc.) - they can't see menu anyway
        return ['type' => 'none', 'value' => null];
    }

    /**
     * Get list of departments for filter dropdown (only for full access users)
     */
    public function getDepartmentsProperty()
    {
        if (!$this->hasFullAccess()) {
            return collect();
        }

        return User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->map(fn($dept) => ['id' => $dept, 'name' => $dept]);
    }

    /**
     * Get list of sections for filter dropdown (only for full access users)
     * If department is selected, filter sections by that department
     */
    public function getSectionsProperty()
    {
        if (!$this->hasFullAccess()) {
            return collect();
        }

        $query = User::whereNotNull('section')
            ->where('section', '!=', '');

        if ($this->filterDepartment) {
            $query->where('department', $this->filterDepartment);
        }

        return $query->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn($section) => ['id' => $section, 'name' => $section]);
    }

    /**
     * Reset section filter when department changes
     */
    public function updatedFilterDepartment(): void
    {
        $this->filterSection = '';
        $this->resetPage();
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'dateRange', 'filterDepartment', 'filterSection']);
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
        if (in_array($property, ['search', 'dateRange', 'filterSection'])) {
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
            ['key' => 'instructor', 'label' => 'Instructor', 'class' => '!text-center w-[180px]'],
            ['key' => 'venue', 'label' => 'Venue', 'class' => '!text-center w-[220px]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center w-[110px]'],
            ['key' => 'employee_name', 'label' => 'Name', 'class' => '!text-center w-[180px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center w-[90px]'],
            ['key' => 'period', 'label' => 'Period', 'class' => '!text-center w-[220px]'],
            ['key' => 'duration', 'label' => 'Duration', 'class' => '!text-center w-[140px]'],
            ['key' => 'pretest_score', 'label' => 'Pre Test', 'class' => '!text-center w-[90px]'],
            ['key' => 'attendance', 'label' => 'Attendance', 'class' => '!text-center w-[100px]'],
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
        $accessFilter = $this->getAccessFilter();

        // If no access, return empty collection
        if ($accessFilter['type'] === 'none') {
            return collect();
        }

        // Get all trainings with status 'approved' within date range
        $query = Training::with([
            'sessions.trainer',
            'assessments.employee',
        ])
            ->where('status', 'approved')
            ->when($dateFrom, fn($q) => $q->whereDate('end_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('end_date', '<=', $dateTo))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));

        // Apply access filter based on user role/position
        // For full access users, apply manual department/section filters if set
        if ($accessFilter['type'] === 'full') {
            if ($this->filterDepartment) {
                $query->whereHas('assessments.employee', function ($q) {
                    $q->where('department', $this->filterDepartment);
                });
            }
            if ($this->filterSection) {
                $query->whereHas('assessments.employee', function ($q) {
                    $q->where('section', $this->filterSection);
                });
            }
        } elseif ($accessFilter['type'] === 'division' && $accessFilter['value']) {
            // Filter trainings that have assessments with employees in the specified division
            $query->whereHas('assessments.employee', function ($q) use ($accessFilter) {
                $q->where('division', $accessFilter['value']);
            });
        } elseif ($accessFilter['type'] === 'section' && $accessFilter['value']) {
            // Filter trainings that have assessments with employees in the specified section
            $query->whereHas('assessments.employee', function ($q) use ($accessFilter) {
                $q->where('section', $accessFilter['value']);
            });
        } elseif ($accessFilter['type'] === 'department' && $accessFilter['value']) {
            // Filter trainings that have assessments with employees in the specified department
            $query->whereHas('assessments.employee', function ($q) use ($accessFilter) {
                $q->where('department', $accessFilter['value']);
            });
        }

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

            // Format totalHours to max 1 decimal
            $totalHours = round($totalHours, 1);

            // For LMS type, show only days; for others, show hours and days
            if (strtoupper($training->type ?? '') === 'LMS') {
                $durationText = $days . ' Days';
            } else {
                $durationText = $totalHours . ' Hours (' . $days . ' Days)';
            }

            // Period
            $startDate = $training->start_date ? Carbon::parse($training->start_date)->format('d-m-Y') : '-';
            $endDate = $training->end_date ? Carbon::parse($training->end_date)->format('d-m-Y') : '-';
            $period = $startDate . ' - ' . $endDate;

            // Filter assessments based on access level (for section/department filtering)
            $assessments = $training->assessments;

            if ($accessFilter['type'] === 'full') {
                // For full access users, apply manual filters if set
                if ($this->filterDepartment) {
                    $assessments = $assessments->filter(function ($assessment) {
                        return $assessment->employee && $assessment->employee->department === $this->filterDepartment;
                    });
                }
                if ($this->filterSection) {
                    $assessments = $assessments->filter(function ($assessment) {
                        return $assessment->employee && $assessment->employee->section === $this->filterSection;
                    });
                }
            } elseif ($accessFilter['type'] === 'division' && $accessFilter['value']) {
                $assessments = $assessments->filter(function ($assessment) use ($accessFilter) {
                    return $assessment->employee && $assessment->employee->division === $accessFilter['value'];
                });
            } elseif ($accessFilter['type'] === 'section' && $accessFilter['value']) {
                $assessments = $assessments->filter(function ($assessment) use ($accessFilter) {
                    return $assessment->employee && $assessment->employee->section === $accessFilter['value'];
                });
            } elseif ($accessFilter['type'] === 'department' && $accessFilter['value']) {
                $assessments = $assessments->filter(function ($assessment) use ($accessFilter) {
                    return $assessment->employee && $assessment->employee->department === $accessFilter['value'];
                });
            }

            // For each assessment (participant)
            foreach ($assessments as $assessment) {
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
                    'attendance' => $assessment->attendance_percentage !== null ? round($assessment->attendance_percentage) . '%' : '-',
                    'attendance_raw' => $assessment->attendance_percentage,
                    'period' => $period,
                    'duration' => $durationText,
                    'pretest_score' => $assessment->pretest_score !== null ? number_format($assessment->pretest_score, 1) : '-',
                    'theory_score' => $assessment->posttest_score !== null ? number_format($assessment->posttest_score, 1) : '-',
                    'practical_score' => $assessment->practical_score !== null ? number_format($assessment->practical_score, 1) : '-',
                    'pretest_raw' => $assessment->pretest_score,
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

        // Pass filter department and section for full access users
        $filterDept = $this->hasFullAccess() ? $this->filterDepartment : null;
        $filterSec = $this->hasFullAccess() ? $this->filterSection : null;

        return Excel::download(
            new TrainingActivityReportExport($dateFrom, $dateTo, $this->search, $filterDept, $filterSec),
            'training_activity_report_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function render()
    {
        return view('pages.reports.training-activity-report', [
            'reports' => $this->reports(),
            'headers' => $this->headers(),
            'hasFullAccess' => $this->hasFullAccess(),
            'departments' => $this->departments,
            'sections' => $this->sections,
        ]);
    }
}
