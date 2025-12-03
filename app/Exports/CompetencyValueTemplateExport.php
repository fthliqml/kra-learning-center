<?php

namespace App\Exports;

use App\Models\Competency;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CompetencyValueTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Competency Value' => new CompetencyValueTemplateSheet(),
            'Competency List' => new CompetencyValueListSheet(),
        ];
    }
}

// Sheet 1: Competency Value Template
class CompetencyValueTemplateSheet implements
    \Maatwebsite\Excel\Concerns\FromArray,
    \Maatwebsite\Excel\Concerns\WithHeadings,
    \Maatwebsite\Excel\Concerns\WithStyles,
    \Maatwebsite\Excel\Concerns\WithEvents,
    \Maatwebsite\Excel\Concerns\WithTitle
{
    use \Maatwebsite\Excel\Concerns\RegistersEventListeners;

    private array $competencyCodes = [];

    public function __construct()
    {
        $this->competencyCodes = Competency::orderBy('code')->pluck('code')->toArray();
    }

    public function title(): string
    {
        return 'Competency Value';
    }

    public function array(): array
    {
        $firstCode = $this->competencyCodes[0] ?? 'BMC001';
        return [
            [$firstCode, 'Division Head', '10%', '5'],
            ['', '', '', ''],
            ['', '', '', ''],
            ['', '', '', ''],
            ['', '', '', ''],
        ];
    }

    public function headings(): array
    {
        return ['Competency Code', 'Position', 'Bobot', 'Value'];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:D1')->applyFromArray([
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

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(20);  // Competency Code
        $sheet->getColumnDimension('B')->setWidth(20);  // Position
        $sheet->getColumnDimension('C')->setWidth(15);  // Bobot
        $sheet->getColumnDimension('D')->setWidth(10);  // Value

        // Add borders to header
        $sheet->getStyle('A1:D1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return $sheet;
    }

    public function registerEvents(): array
    {
        $competencyCodes = $this->competencyCodes;

        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) use ($competencyCodes) {
                $sheet = $event->sheet->getDelegate();

                // Add dropdown for Competency Code column (A2:A100)
                if (!empty($competencyCodes)) {
                    $codeList = implode(',', $competencyCodes);
                    for ($row = 2; $row <= 100; $row++) {
                        $validation = $sheet->getCell("A{$row}")->getDataValidation();
                        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                        $validation->setAllowBlank(true);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);
                        $validation->setErrorTitle('Invalid Competency');
                        $validation->setError('Please select a valid competency code from the dropdown.');
                        $validation->setPromptTitle('Select Competency');
                        $validation->setPrompt('Choose competency code from Competency List');
                        $validation->setFormula1('"' . $codeList . '"');
                    }
                }

                // Add dropdown for Position column (B2:B100)
                for ($row = 2; $row <= 100; $row++) {
                    $validation = $sheet->getCell("B{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid Position');
                    $validation->setError('Please select a valid position from the dropdown.');
                    $validation->setPromptTitle('Select Position');
                    $validation->setPrompt('Choose position level');
                    $validation->setFormula1('"Division Head,Department Head,Section Head,Foreman,Staff"');
                }

                // Add dropdown for Value column (D2:D100)
                for ($row = 2; $row <= 100; $row++) {
                    $validation = $sheet->getCell("D{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid Value');
                    $validation->setError('Please select a value from 1 to 10.');
                    $validation->setPromptTitle('Select Value');
                    $validation->setPrompt('Choose value from 1 to 10');
                    $validation->setFormula1('"1,2,3,4,5,6,7,8,9,10"');
                }

                // Add instruction note
                $sheet->setCellValue('A102', 'Note:');
                $sheet->setCellValue('B102', 'Competency Code and Position have dropdown selections. Bobot is free text (e.g., 10%, 15%, 1). Value is 1-10.');
                $sheet->mergeCells('B102:D102');
                $sheet->getStyle('A102:D102')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
                ]);
            },
        ];
    }
}

// Sheet 2: Competency List Reference
class CompetencyValueListSheet implements
    \Maatwebsite\Excel\Concerns\FromCollection,
    \Maatwebsite\Excel\Concerns\WithHeadings,
    \Maatwebsite\Excel\Concerns\WithStyles,
    \Maatwebsite\Excel\Concerns\WithTitle
{
    public function title(): string
    {
        return 'Competency List';
    }

    public function collection()
    {
        return Competency::orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->map(function ($item, $index) {
                return [
                    'no' => $index + 1,
                    'code' => $item->code,
                    'name' => $item->name,
                    'type' => $item->type,
                ];
            });
    }

    public function headings(): array
    {
        return ['No', 'Code', 'Competency Name', 'Type'];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '90EE90'], // Light Green
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);   // No
        $sheet->getColumnDimension('B')->setWidth(15);  // Code
        $sheet->getColumnDimension('C')->setWidth(40);  // Competency Name
        $sheet->getColumnDimension('D')->setWidth(10);  // Type

        // Add borders to header
        $sheet->getStyle('A1:D1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Style data rows
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:D' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center align No, Code, and Type columns
            $sheet->getStyle('A2:A' . $highestRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B2:B' . $highestRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D2:D' . $highestRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }

        return $sheet;
    }
}
