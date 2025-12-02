<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingTemplateExport implements FromCollection, WithHeadings, WithStyles
{
  public function collection()
  {
    // Provide one sample row (optional) or just empty collection.
    return collect([]);
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
    $lastCol = 'M';
    $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
      'font' => ['bold' => true],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'EFEFEF'],
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
  }
}
