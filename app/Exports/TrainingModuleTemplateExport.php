<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingModuleTemplateExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return ['No', 'Module Title', 'Competency Code', 'Objective', 'Training Content', 'Method', 'Duration (hours)', 'Frequency (days)'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ADD8E6'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],

        ]);

        // Set column width
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
    }
}
