<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CompetencyBookTemplateExport implements FromArray, WithHeadings, WithStyles, WithEvents
{
    /**
     * Empty data for the template.
     */
    public function array(): array
    {
        // Return empty rows for user to fill
        return [
            ['', '', ''],
            ['', '', ''],
            ['', '', ''],
            ['', '', ''],
            ['', '', ''],
        ];
    }

    /**
     * Headings for the template.
     */
    public function headings(): array
    {
        return ['Competency Name', 'Type', 'Description'];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header (row 1)
        $sheet->getStyle('A1:C1')->applyFromArray([
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
        $sheet->getColumnDimension('A')->setWidth(40);  // Competency Name
        $sheet->getColumnDimension('B')->setWidth(15);  // Type
        $sheet->getColumnDimension('C')->setWidth(50);  // Description

        // Style all cells
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add dropdown validation for Type column (B2:B100)
                for ($row = 2; $row <= 100; $row++) {
                    $validation = $sheet->getCell("B{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid Type');
                    $validation->setError('Please select a valid type from the dropdown.');
                    $validation->setPromptTitle('Select Type');
                    $validation->setPrompt('Choose competency type: BMC, BC, MMP, LC, MDP, or TOC');
                    $validation->setFormula1('"BMC,BC,MMP,LC,MDP,TOC"');
                }
            },
        ];
    }
}
