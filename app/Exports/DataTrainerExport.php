<?php

namespace App\Exports;

use App\Models\Trainer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataTrainerExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * Build collection of trainer data with related user and competencies.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $trainers = Trainer::with(['user', 'competencies'])
            ->orderByDesc('created_at')
            ->get();

        return $trainers->values()->map(function ($trainer, $index) {
            // Handle trainer type and name
            $trainerType = $trainer->user_id ? 'Internal' : 'External';
            $name = $trainer->user_id ?
                optional($trainer->user)->name : ($trainer->name ?? 'N/A');

            $institution = $trainer->institution;
            $competencies = $trainer->competencies
                ->pluck('description')
                ->filter()
                ->implode(', ');

            return [
                $index + 1,       // No
                $trainerType,     // Trainer Type (Internal/External)
                $name,            // Name
                $institution,     // Institution
                $competencies,    // Competencies (comma-separated)
            ];
        });
    }

    /**
     * Headings for the export.
     */
    public function headings(): array
    {
        return ['No', 'Trainer Type', 'Name', 'Institution', 'Competencies'];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header (row 1)
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ADD8E6'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);   // No
        $sheet->getColumnDimension('B')->setWidth(15);  // Trainer Type
        $sheet->getColumnDimension('C')->setWidth(30);  // Name
        $sheet->getColumnDimension('D')->setWidth(24);  // Institution
        $sheet->getColumnDimension('E')->setWidth(50);  // Competencies

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

        // Left align Institution and Competencies for readability
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('D2:E' . $highestRow)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }
    }
}
