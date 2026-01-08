<?php

namespace App\Exports;

use App\Models\Competency;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DataTrainerTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Data Trainer' => new DataTrainerTemplateSheet(),
            'User List' => new UserListSheet(),
        ];
    }
}

// Sheet 1: Data Trainer Template
class DataTrainerTemplateSheet implements
    \Maatwebsite\Excel\Concerns\FromArray,
    \Maatwebsite\Excel\Concerns\WithHeadings,
    \Maatwebsite\Excel\Concerns\WithStyles,
    \Maatwebsite\Excel\Concerns\WithEvents,
    \Maatwebsite\Excel\Concerns\WithTitle
{
    use \Maatwebsite\Excel\Concerns\RegistersEventListeners;

    public function title(): string
    {
        return 'Data Trainer';
    }

    public function array(): array
    {
        return [
            ['1', 'Internal', 'John Doe', 'PT Komatsu Remanufacturing Asia'],
            ['', '', '', ''],
            ['', '', '', ''],
            ['', '', '', ''],
            ['', '', '', ''],
        ];
    }

    public function headings(): array
    {
        return ['No', 'Trainer Type', 'Name', 'Institution'];
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
        $sheet->getColumnDimension('A')->setWidth(6);   // No
        $sheet->getColumnDimension('B')->setWidth(15);  // Trainer Type
        $sheet->getColumnDimension('C')->setWidth(30);  // Name
        $sheet->getColumnDimension('D')->setWidth(30);  // Institution

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
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add dropdown for Trainer Type column (B2:B100)
                for ($row = 2; $row <= 100; $row++) {
                    $validation = $sheet->getCell("B{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid Type');
                    $validation->setError('Please select Internal or External.');
                    $validation->setPromptTitle('Select Trainer Type');
                    $validation->setPrompt('Choose: Internal or External');
                    $validation->setFormula1('"Internal,External"');
                }

                // Add instruction note
                $sheet->setCellValue('A102', 'Note:');
                $sheet->setCellValue('B102', 'For Internal trainers, use names from "User List" sheet.');
                $sheet->mergeCells('B102:D102');
                $sheet->getStyle('A102:D102')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
                ]);
            },
        ];
    }
}

// Sheet 2: User List Reference (for Internal trainers)
class UserListSheet implements
    \Maatwebsite\Excel\Concerns\FromCollection,
    \Maatwebsite\Excel\Concerns\WithHeadings,
    \Maatwebsite\Excel\Concerns\WithStyles,
    \Maatwebsite\Excel\Concerns\WithTitle
{
    public function title(): string
    {
        return 'User List';
    }

    public function collection()
    {
        return User::orderBy('name')
            ->get(['id', 'name', 'NRP', 'section'])
            ->map(function ($item, $index) {
                return [
                    'no' => $index + 1,
                    'name' => $item->name,
                    'nrp' => $item->NRP,
                    'section' => $item->section,
                ];
            });
    }

    public function headings(): array
    {
        return ['No', 'Name', 'NRP', 'Section'];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFD700'], // Gold/Yellow
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);   // No
        $sheet->getColumnDimension('B')->setWidth(30);  // Name
        $sheet->getColumnDimension('C')->setWidth(15);  // NRP
        $sheet->getColumnDimension('D')->setWidth(25);  // Section

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
}

// (Competency List sheet removed: trainer import no longer uses competencies)
