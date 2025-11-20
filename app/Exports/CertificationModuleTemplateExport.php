<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CertificationModuleTemplateExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    public function collection(): Collection
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'Code',
            'Module Title',
            'Competency',
            'Level',
            'Group Certification',
            'Points Per Module',
            'New Gex',
            'Duration (minutes)',
            'Theory Passing Score (%)',
            'Practical Passing Score (%)',
            'Major Component',
            'Mach Model',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'L'; // 12 columns
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
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

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply dropdown validations for rows 2..500
                $startRow = 2;
                $endRow = 500;

                // Level (Column D) options
                $levelList = '"Basic,Intermediate,Advanced"';

                // Group Certification (Column E) options
                $groupList = '"ENGINE,MACHINING,PPT AND PPM"';

                for ($row = $startRow; $row <= $endRow; $row++) {
                    // Level dropdown (D$row)
                    $levelCell = 'D' . $row;
                    $lvlValidation = $sheet->getCell($levelCell)->getDataValidation();
                    $lvlValidation->setType(DataValidation::TYPE_LIST);
                    $lvlValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $lvlValidation->setAllowBlank(false);
                    $lvlValidation->setShowInputMessage(true);
                    $lvlValidation->setShowErrorMessage(true);
                    $lvlValidation->setShowDropDown(true);
                    $lvlValidation->setErrorTitle('Invalid input');
                    $lvlValidation->setError('Value is not in the allowed list.');
                    $lvlValidation->setPromptTitle('Select from list');
                    $lvlValidation->setPrompt('Please select a value from the dropdown.');
                    $lvlValidation->setFormula1($levelList);

                    // Group Certification dropdown (E$row)
                    $groupCell = 'E' . $row;
                    $grpValidation = $sheet->getCell($groupCell)->getDataValidation();
                    $grpValidation->setType(DataValidation::TYPE_LIST);
                    $grpValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $grpValidation->setAllowBlank(false);
                    $grpValidation->setShowInputMessage(true);
                    $grpValidation->setShowErrorMessage(true);
                    $grpValidation->setShowDropDown(true);
                    $grpValidation->setErrorTitle('Invalid input');
                    $grpValidation->setError('Value is not in the allowed list.');
                    $grpValidation->setPromptTitle('Select from list');
                    $grpValidation->setPrompt('Please select a value from the dropdown.');
                    $grpValidation->setFormula1($groupList);
                }

                // Freeze header row
                $sheet->freezePane('A2');
            }
        ];
    }
}
