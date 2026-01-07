<?php

namespace App\Exports;

use App\Models\Request as TrainingRequestModel;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingRequestExport implements FromCollection, WithHeadings, WithStyles
{
    protected ?User $user;
    protected string $statusFilter;
    protected string $search;

    public function __construct(?User $user, string $statusFilter = 'all', string $search = '')
    {
        $this->user = $user;
        $this->statusFilter = strtolower($statusFilter ?: 'all');
        $this->search = trim($search);
    }

    public function collection(): Collection
    {
        $query = TrainingRequestModel::query()
            ->leftJoin('users', 'training_requests.created_by', '=', 'users.id')
            ->leftJoin('users as u2', 'training_requests.user_id', '=', 'u2.id')
            ->leftJoin('competency', 'training_requests.competency_id', '=', 'competency.id')
            ->select([
                'training_requests.id',
                'training_requests.created_at',
                'training_requests.status',
                'training_requests.approval_stage',
                'users.name as created_by_name',
                'u2.nrp as employee_nrp',
                'u2.name as employee_name',
                'u2.section as section',
                'u2.department as department',
                'u2.division as division',
                'competency.name as competency_name',
                'competency.type as competency_type',
                'training_requests.reason',
            ]);

        // Apply status filter from page (Pending/Approved/Rejected)
        if ($this->statusFilter !== '' && $this->statusFilter !== 'all') {
            $query->where('training_requests.status', $this->statusFilter);
        }

        // Apply search filter (same fields as index table)
        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('competency.name', 'like', $term)
                    ->orWhere('training_requests.reason', 'like', $term)
                    ->orWhere('users.name', 'like', $term)
                    ->orWhere('u2.name', 'like', $term)
                    ->orWhere('u2.section', 'like', $term);
            });
        }

        // Scope by role/position: currently export is restricted at caller level
        // (only admin or LID Section Head can trigger export), so we do not
        // apply additional area restrictions here.
        if ($this->user instanceof User) {
            // Placeholder for future role-based scoping if needed.
        }

        $rows = $query->orderBy('training_requests.created_at', 'desc')->get();

        return $rows->values()->map(function ($row, int $index) {
            $status = strtolower($row->status ?? TrainingRequestModel::STATUS_PENDING);
            $stage = strtolower($row->approval_stage ?? TrainingRequestModel::STAGE_DEPT_HEAD);

            if ($status === TrainingRequestModel::STATUS_PENDING) {
                $approvalLabel = match ($stage) {
                    TrainingRequestModel::STAGE_DEPT_HEAD => 'Pending Dept Head',
                    TrainingRequestModel::STAGE_AREA_DIV_HEAD => 'Pending Division Head Area',
                    TrainingRequestModel::STAGE_LID_DIV_HEAD => 'Pending Division Head LID',
                    default => 'Pending',
                };
            } elseif ($status === TrainingRequestModel::STATUS_REJECTED) {
                $approvalLabel = match ($stage) {
                    TrainingRequestModel::STAGE_DEPT_HEAD => 'Rejected by Dept Head',
                    TrainingRequestModel::STAGE_AREA_DIV_HEAD => 'Rejected by Division Head Area',
                    TrainingRequestModel::STAGE_LID_DIV_HEAD => 'Rejected by Division Head LID',
                    default => 'Rejected',
                };
            } else {
                // Approved or other terminal statuses
                $approvalLabel = ucfirst($status);
            }

            return [
                $index + 1,
                optional($row->created_at)->format('Y-m-d H:i'),
                $row->created_by_name,
                $row->employee_nrp,
                $row->employee_name,
                $row->section,
                $row->department,
                $row->division,
                $row->competency_type,
                $row->competency_name,
                $row->reason,
                $status,
                $approvalLabel,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Requested At',
            'Created By',
            'NRP',
            'Employee Name',
            'Section',
            'Department',
            'Division',
            'Group Comp',
            'Competency',
            'Reason',
            'Status',
            'Approval Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'M'; // 13 columns
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D1E8FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $widths = [
            'A' => 6,
            'B' => 20,
            'C' => 24,
            'D' => 10,
            'E' => 26,
            'F' => 18,
            'G' => 26,
            'H' => 26,
            'I' => 18,
            'J' => 30,
            'K' => 40,
            'L' => 12,
            'M' => 26,
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'alignment' => [
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
    }
}
