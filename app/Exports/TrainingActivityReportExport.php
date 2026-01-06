<?php

namespace App\Exports;

use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingActivityReportExport implements FromCollection, WithHeadings, WithStyles, WithMapping
{
    protected $dateFrom;
    protected $dateTo;
    protected $search;
    protected $rowNumber = 0;
    protected $accessFilter;
    protected $filterDepartment;
    protected $filterSection;

    public function __construct($dateFrom = null, $dateTo = null, $search = '', $filterDepartment = null, $filterSection = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->search = $search;
        $this->filterDepartment = $filterDepartment;
        $this->filterSection = $filterSection;
        $this->accessFilter = $this->getAccessFilter();
    }

    /**
     * Check if current user has full access to all training data
     * Full access: admin, instructor, certificator, multimedia roles, or section_head with section "LID"
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

        return false;
    }

    /**
     * Get the access filter type and value for current user
     * Based on menu.php: only section_head position and specific roles can access this menu
     */
    protected function getAccessFilter(): array
    {
        $user = Auth::user();

        if (!$user) {
            return ['type' => 'none', 'value' => null];
        }

        if ($this->hasFullAccess()) {
            return ['type' => 'full', 'value' => null];
        }

        if ($user->hasPosition('section_head')) {
            return ['type' => 'section', 'value' => $user->section];
        }

        if ($user->hasPosition('department_head')) {
            return ['type' => 'department', 'value' => $user->department];
        }

        // No access for other positions (employee, supervisor, etc.) - they can't see menu anyway
        return ['type' => 'none', 'value' => null];
    }

    public function collection()
    {
        // If no access, return empty collection
        if ($this->accessFilter['type'] === 'none') {
            return collect();
        }

        $query = Training::with([
            'sessions.trainer',
            'assessments.employee',
        ])
            ->where('status', 'approved')
            ->when($this->dateFrom, fn($q) => $q->whereDate('end_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('end_date', '<=', $this->dateTo))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));

        // Apply access filter based on user role/position
        // For full access users, apply manual department/section filters if set
        if ($this->accessFilter['type'] === 'full') {
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
        } elseif ($this->accessFilter['type'] === 'section' && $this->accessFilter['value']) {
            $query->whereHas('assessments.employee', function ($q) {
                $q->where('section', $this->accessFilter['value']);
            });
        } elseif ($this->accessFilter['type'] === 'department' && $this->accessFilter['value']) {
            $query->whereHas('assessments.employee', function ($q) {
                $q->where('department', $this->accessFilter['value']);
            });
        }

        $trainings = $query->orderBy('end_date', 'desc')->get();

        // Flatten data
        $reports = collect();

        foreach ($trainings as $training) {
            $firstSession = $training->sessions->first();
            $instructor = $firstSession?->trainer?->name ?? ($firstSession?->trainer?->user?->name ?? '-');
            $venue = $firstSession
                ? trim(($firstSession->room_location ?? '') . ' - ' . ($firstSession->room_name ?? ''), ' -')
                : '-';
            if ($venue === '-' || empty(trim($venue, ' -'))) {
                $venue = '-';
            }

            $days = $training->duration;
            $totalHours = $training->sessions->count() > 0
                ? $training->sessions->sum(function ($session) {
                    if ($session->start_time && $session->end_time) {
                        return Carbon::parse($session->start_time)->diffInHours(Carbon::parse($session->end_time));
                    }
                    return 0;
                })
                : ($days * 8);

            // Format totalHours to max 1 decimal
            $totalHours = round($totalHours, 1);

            // For LMS trainings, only show days; others show hours and days
            if (strtoupper($training->type ?? '') === 'LMS') {
                $durationText = $days . ' Days';
            } else {
                $durationText = $totalHours . ' Hours (' . $days . ' Days)';
            }

            $startDate = $training->start_date ? Carbon::parse($training->start_date)->format('d M Y') : '-';
            $endDate = $training->end_date ? Carbon::parse($training->end_date)->format('d M Y') : '-';
            $period = $startDate . ' - ' . $endDate;

            // Filter assessments based on access level
            $assessments = $training->assessments;

            if ($this->accessFilter['type'] === 'full') {
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
            } elseif ($this->accessFilter['type'] === 'section' && $this->accessFilter['value']) {
                $assessments = $assessments->filter(function ($assessment) {
                    return $assessment->employee && $assessment->employee->section === $this->accessFilter['value'];
                });
            } elseif ($this->accessFilter['type'] === 'department' && $this->accessFilter['value']) {
                $assessments = $assessments->filter(function ($assessment) {
                    return $assessment->employee && $assessment->employee->department === $this->accessFilter['value'];
                });
            }

            foreach ($assessments as $assessment) {
                $employee = $assessment->employee;

                $reports->push((object) [
                    'training' => $training,
                    'assessment' => $assessment,
                    'employee' => $employee,
                    'instructor' => $instructor,
                    'venue' => $venue,
                    'period' => $period,
                    'duration' => $durationText,
                ]);
            }
        }

        return $reports;
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $training = $row->training;
        $assessment = $row->assessment;
        $employee = $row->employee;

        return [
            $this->rowNumber,
            'TRN-' . str_pad($training->id, 5, '0', STR_PAD_LEFT),
            $training->name ?? '-',
            $training->group_comp ?? '-',
            ucfirst($training->type ?? '-'),
            $row->instructor,
            $row->venue,
            $employee->nrp ?? '-',
            $employee->name ?? '-',
            $employee->section ?? '-',
            $row->period,
            $row->duration,
            $assessment->pretest_score !== null ? number_format($assessment->pretest_score, 1) : '-',
            $assessment->attendance_percentage !== null ? round($assessment->attendance_percentage) . '%' : '-',
            $assessment->posttest_score !== null ? number_format($assessment->posttest_score, 1) : '-',
            $assessment->practical_score !== null ? number_format($assessment->practical_score, 1) : '-',
            ucfirst($assessment->status ?? 'failed'),
            $training->end_date ? Carbon::parse($training->end_date)->format('d M Y') : '-',
            $assessment->status === 'passed' ? 'Yes' : 'No',
            $assessment->notes ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Event Code',
            'Training Name',
            'Group Comp',
            'Type',
            'Instructor',
            'Venue',
            'NRP',
            'Name',
            'Section',
            'Period',
            'Duration',
            'Pre Test Score',
            'Attendance',
            'Theory Score',
            'Practical Score',
            'Remarks',
            'Date Report',
            'Certificate',
            'Note',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header (row 1)
        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ADD8E6'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);   // No
        $sheet->getColumnDimension('B')->setWidth(12);  // Event Code
        $sheet->getColumnDimension('C')->setWidth(35);  // Training Name
        $sheet->getColumnDimension('D')->setWidth(12);  // Group Comp
        $sheet->getColumnDimension('E')->setWidth(10);  // Type
        $sheet->getColumnDimension('F')->setWidth(20);  // Instructor
        $sheet->getColumnDimension('G')->setWidth(25);  // Venue
        $sheet->getColumnDimension('H')->setWidth(12);  // NRP
        $sheet->getColumnDimension('I')->setWidth(20);  // Name
        $sheet->getColumnDimension('J')->setWidth(10);  // Section
        $sheet->getColumnDimension('K')->setWidth(12);  // Attendance
        $sheet->getColumnDimension('L')->setWidth(25);  // Period
        $sheet->getColumnDimension('M')->setWidth(18);  // Duration
        $sheet->getColumnDimension('N')->setWidth(12);  // Pre Test Score
        $sheet->getColumnDimension('O')->setWidth(12);  // Theory Score
        $sheet->getColumnDimension('P')->setWidth(12);  // Practical Score
        $sheet->getColumnDimension('Q')->setWidth(10);  // Remarks
        $sheet->getColumnDimension('R')->setWidth(12);  // Date Report
        $sheet->getColumnDimension('S')->setWidth(12);  // Certificate
        $sheet->getColumnDimension('T')->setWidth(20);  // Note

        // Style all cells
        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Left align text columns
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('T2:T' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    }
}
