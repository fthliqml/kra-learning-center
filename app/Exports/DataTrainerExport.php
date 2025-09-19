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
            $name = optional($trainer->user)->name;
            $institution = $trainer->institution;
            $competencies = $trainer->competencies
                ->pluck('description')
                ->filter()
                ->implode(', ');

            return [
                $index + 1,       // No
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
        return ['No', 'Name', 'Institution', 'Competencies'];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header (row 1)
        $sheet->getStyle('A1:D1')->applyFromArray([
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
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(30); // Name
        $sheet->getColumnDimension('C')->setWidth(24); // Institution
        $sheet->getColumnDimension('D')->setWidth(50); // Competencies

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
            $sheet->getStyle('C2:D' . $highestRow)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }
    }
}
