<?php

namespace App\Exports;

use App\Models\Training;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DevelopmentRecapExport implements FromCollection, WithHeadings, WithStyles
{
    protected int $year;
    protected ?string $search;

    public function __construct(int $year, ?string $search = null)
    {
        $this->year = $year;
        $this->search = $search ? trim($search) : null;
    }

    public function collection(): Collection
    {
        $query = User::query()
            ->whereHas('trainingPlans', function ($q) {
                $q->where('year', $this->year)
                    ->where('status', TrainingPlan::STATUS_APPROVED);
            })
            ->with(['trainingPlans' => function ($q) {
                $q->where('year', $this->year)
                    ->where('status', TrainingPlan::STATUS_APPROVED)
                    ->with('competency')
                    ->orderBy('id');
            }]);

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('nrp', 'like', $term)
                    ->orWhere('section', 'like', $term);
            });
        }

        $users = $query->orderBy('name')->get();

        $rows = [];
        $no = 1;

        foreach ($users as $user) {
            $plans = $user->trainingPlans->take(3)->values();

            $planLabels = $plans->map(function (TrainingPlan $plan) {
                return $plan->competency?->name ?? '-';
            });

            $competencyIds = $plans
                ->pluck('competency_id')
                ->filter()
                ->unique()
                ->values();

            $hasScheduledTraining = false;

            if ($competencyIds->isNotEmpty()) {
                $hasScheduledTraining = Training::query()
                    ->whereYear('start_date', $this->year)
                    ->where(function ($q) use ($competencyIds) {
                        $q->whereIn('competency_id', $competencyIds)
                            ->orWhereHas('module', function ($mq) use ($competencyIds) {
                                $mq->whereIn('competency_id', $competencyIds);
                            })
                            ->orWhereHas('course', function ($cq) use ($competencyIds) {
                                $cq->whereIn('competency_id', $competencyIds);
                            });
                    })
                    ->exists();
            }

            $statusLabel = $hasScheduledTraining ? 'Scheduled' : 'Waiting';

            $rows[] = [
                $no++,
                $user->nrp,
                $user->name,
                $user->section,
                $planLabels[0] ?? '-',
                $planLabels[1] ?? '-',
                $planLabels[2] ?? '-',
                $statusLabel,
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return ['No', 'NRP', 'Employee Name', 'Section', 'Plan 1', 'Plan 2', 'Plan 3', 'Status'];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style (same approach as TrainingRequestExport)
        $lastCol = 'H'; // 8 columns
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

        // Column widths (mirroring style from TrainingRequestExport)
        $widths = [
            'A' => 6,  // No
            'B' => 12, // NRP
            'C' => 26, // Employee Name
            'D' => 20, // Section
            'E' => 30, // Plan 1
            'F' => 30, // Plan 2
            'G' => 30, // Plan 3
            'H' => 16, // Status
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Global cell styles: borders + vertical alignment + wrap text
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
