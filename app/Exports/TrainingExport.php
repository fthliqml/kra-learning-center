<?php

namespace App\Exports;

use App\Models\Training;
use App\Models\TrainingSession;
use App\Models\TrainingAssessment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        $trainings = Training::with([
            'sessions' => function ($q) {
                $q->orderBy('day_number');
            },
            'assessments.employee',
            'course'
        ])
            ->orderBy('start_date')
            ->get();

        $rows = [];
        $counter = 1;
        foreach ($trainings as $training) {
            $firstSession = $training->sessions->first();
            $participants = $training->assessments
                ->map(fn($a) => $a->employee?->name)
                ->filter()
                ->implode(', ');

            $rows[] = [
                'no' => $counter++,
                // BLENDED and LMS: use course title as training name
                'training_name' => in_array($training->type, ['LMS', 'BLENDED']) 
                    ? ($training->course?->title ?? $training->name) 
                    : $training->name,
                'type' => $training->type,
                'group_comp' => $training->group_comp,
                'start_date' => optional($training->start_date)->format('Y-m-d'),
                'end_date' => optional($training->end_date)->format('Y-m-d'),
                // LMS has no trainer; BLENDED and IN/OUT have trainer
                'trainer_name' => $training->type === 'LMS' ? '' : ($firstSession?->trainer?->name ?? $firstSession?->trainer?->user?->name ?? ''),
                'room_name' => $firstSession?->room_name ?? '',
                'room_location' => $firstSession?->room_location ?? '',
                // LMS has no times; BLENDED and IN/OUT have times
                'start_time' => $training->type === 'LMS' ? '' : ($firstSession?->start_time ? date('H:i', strtotime($firstSession->start_time)) : ''),
                'end_time' => $training->type === 'LMS' ? '' : ($firstSession?->end_time ? date('H:i', strtotime($firstSession->end_time)) : ''),
                // LMS and BLENDED have course title
                'course_title' => in_array($training->type, ['LMS', 'BLENDED']) ? ($training->course?->title ?? '') : '',
                'participants' => $participants,
            ];
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'No',
            'Training Name',
            'Type',
            'Group Comp',
            'Start Date',
            'End Date',
            'Trainer Name',
            'Room Name',
            'Room Location',
            'Start Time',
            'End Time',
            'Course Title (LMS)',
            'Participants',
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
            'A' => 5,
            'B' => 40,
            'C' => 10,
            'D' => 14,
            'E' => 14,
            'F' => 14,
            'G' => 30,
            'H' => 20,
            'I' => 22,
            'J' => 12,
            'K' => 12,
            'L' => 40,
            'M' => 50,
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
