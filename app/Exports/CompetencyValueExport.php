<?php

namespace App\Exports;

use App\Models\CompetencyValue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompetencyValueExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * Build collection of competency value data.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $competencyValues = CompetencyValue::with('competency')
            ->orderByDesc('created_at')
            ->get();

        return $competencyValues->values()->map(function ($cv, $index) {
            return [
                $index + 1,                         // No
                $cv->competency?->code ?? '',       // ID (Competency Code)
                $cv->competency?->name ?? '',       // Competency Name
                $cv->competency?->type ?? '',       // Type
                $cv->position,                      // Position
                $cv->bobot,                         // Bobot
                $cv->value,                         // Value
            ];
        });
    }

    /**
     * Headings for the export.
     */
    public function headings(): array
    {
        return ['No', 'ID', 'Competency Name', 'Type', 'Position', 'Bobot', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header (row 1)
        $sheet->getStyle('A1:G1')->applyFromArray([
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
        $sheet->getColumnDimension('B')->setWidth(12);  // ID
        $sheet->getColumnDimension('C')->setWidth(40);  // Competency Name
        $sheet->getColumnDimension('D')->setWidth(10);  // Type
        $sheet->getColumnDimension('E')->setWidth(20);  // Position
        $sheet->getColumnDimension('F')->setWidth(15);  // Bobot
        $sheet->getColumnDimension('G')->setWidth(10);  // Value

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

        // Left align Competency Name for readability
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('C2:C' . $highestRow)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }
    }
}
