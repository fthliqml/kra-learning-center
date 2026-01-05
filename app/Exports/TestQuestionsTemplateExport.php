<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TestQuestionsTemplateExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
  protected string $type;

  public function __construct(string $type = 'pretest')
  {
    $this->type = $type;
  }

  public function collection()
  {
    // Sample data to show format
    return collect([
      [
        'no' => 1,
        'type' => 'multiple',
        'question' => 'What is the capital of Indonesia?',
        'option_a' => 'Jakarta',
        'option_b' => 'Bandung',
        'option_c' => 'Surabaya',
        'option_d' => 'Medan',
        'option_e' => '',
        'correct_answer' => 'A',
      ],
      [
        'no' => 2,
        'type' => 'multiple',
        'question' => 'Which planet is known as the Red Planet?',
        'option_a' => 'Venus',
        'option_b' => 'Mars',
        'option_c' => 'Jupiter',
        'option_d' => 'Saturn',
        'option_e' => '',
        'correct_answer' => 'B',
      ],
      [
        'no' => 3,
        'type' => 'essay',
        'question' => 'Explain the importance of safety in the workplace.',
        'option_a' => '',
        'option_b' => '',
        'option_c' => '',
        'option_d' => '',
        'option_e' => '',
        'correct_answer' => '',
      ],
    ]);
  }

  public function headings(): array
  {
    return [
      'No',
      'Type',
      'Question',
      'Option A',
      'Option B',
      'Option C',
      'Option D',
      'Option E',
      'Correct Answer',
    ];
  }

  public function title(): string
  {
    return ucfirst($this->type) . ' Questions';
  }

  public function styles(Worksheet $sheet)
  {
    $lastCol = 'I';

    // Header style
    $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4863A0'],
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      ],
    ]);

    // Column widths
    $widths = [
      'A' => 6,   // No
      'B' => 12,  // Type
      'C' => 50,  // Question
      'D' => 30,  // Option A
      'E' => 30,  // Option B
      'F' => 30,  // Option C
      'G' => 30,  // Option D
      'H' => 30,  // Option E
      'I' => 15,  // Correct Answer
    ];

    foreach ($widths as $col => $w) {
      $sheet->getColumnDimension($col)->setWidth($w);
    }

    // Add instructions comment to the first cell
    $sheet->getComment('B1')->getText()->createTextRun(
      "Type: 'multiple' for multiple choice, 'essay' for essay questions"
    );
    $sheet->getComment('I1')->getText()->createTextRun(
      "For multiple choice: Enter A, B, C, D, or E\nLeave empty for essay questions"
    );

    // Text wrap for question column
    $sheet->getStyle('C:C')->getAlignment()->setWrapText(true);

    // Sample data styling (lighter background)
    $lastRow = $sheet->getHighestRow();
    if ($lastRow > 1) {
      $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray([
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['rgb' => 'F5F5F5'],
        ],
      ]);
    }

    return [];
  }
}
