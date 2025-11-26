<?php

namespace App\Exports;

use App\Models\Training;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingActivityReportExport implements FromCollection, WithHeadings, WithStyles, WithMapping
{
  protected $dateFrom;
  protected $dateTo;
  protected $search;
  protected $rowNumber = 0;

  public function __construct($dateFrom = null, $dateTo = null, $search = '')
  {
    $this->dateFrom = $dateFrom;
    $this->dateTo = $dateTo;
    $this->search = $search;
  }

  public function collection()
  {
    $query = Training::with([
      'sessions.trainer',
      'assessments.employee',
    ])
      ->where('status', 'done')
      ->when($this->dateFrom, fn($q) => $q->whereDate('end_date', '>=', $this->dateFrom))
      ->when($this->dateTo, fn($q) => $q->whereDate('end_date', '<=', $this->dateTo))
      ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));

    $trainings = $query->orderBy('end_date', 'desc')->get();

    // Flatten data
    $reports = collect();

    foreach ($trainings as $training) {
      $firstSession = $training->sessions->first();
      $instructor = $firstSession?->trainer?->name ?? ($firstSession?->trainer?->user?->name ?? '-');
      $venue = $firstSession
        ? trim(($firstSession->room_location ?? '') . ' - ' . ($firstSession->room_name ?? ''), ' -')
        : '-';
      if ($venue === '-' || empty(trim($venue, ' -'))) {
        $venue = '-';
      }

      $days = $training->duration;
      $totalHours = $training->sessions->count() > 0
        ? $training->sessions->sum(function ($session) {
          if ($session->start_time && $session->end_time) {
            return Carbon::parse($session->start_time)->diffInHours(Carbon::parse($session->end_time));
          }
          return 0;
        })
        : ($days * 8);

      $durationText = $totalHours . ' Hours (' . $days . ' Days)';

      $startDate = $training->start_date ? Carbon::parse($training->start_date)->format('d M Y') : '-';
      $endDate = $training->end_date ? Carbon::parse($training->end_date)->format('d M Y') : '-';
      $period = $startDate . ' - ' . $endDate;

      foreach ($training->assessments as $assessment) {
        $employee = $assessment->employee;

        $reports->push((object) [
          'training' => $training,
          'assessment' => $assessment,
          'employee' => $employee,
          'instructor' => $instructor,
          'venue' => $venue,
          'period' => $period,
          'duration' => $durationText,
        ]);
      }
    }

    return $reports;
  }

  public function map($row): array
  {
    $this->rowNumber++;
    $training = $row->training;
    $assessment = $row->assessment;
    $employee = $row->employee;

    return [
      $this->rowNumber,
      'TRN-' . str_pad($training->id, 5, '0', STR_PAD_LEFT),
      $training->name ?? '-',
      $training->group_comp ?? '-',
      ucfirst($training->type ?? '-'),
      $row->instructor,
      $row->venue,
      $employee->nrp ?? '-',
      $employee->name ?? '-',
      $employee->section ?? '-',
      $row->period,
      $row->duration,
      $assessment->posttest_score !== null ? number_format($assessment->posttest_score, 1) : '-',
      $assessment->practical_score !== null ? number_format($assessment->practical_score, 1) : '-',
      ucfirst($assessment->status ?? 'failed'),
      $training->end_date ? Carbon::parse($training->end_date)->format('d M Y') : '-',
      $assessment->status === 'passed' ? 'Yes' : 'No',
      $assessment->notes ?? '-',
    ];
  }

  public function headings(): array
  {
    return [
      'No',
      'Event Code',
      'Training Name',
      'Group Comp',
      'Type',
      'Instructor',
      'Venue',
      'NRP',
      'Name',
      'Section',
      'Period',
      'Duration',
      'Theory Score',
      'Practical Score',
      'Remarks',
      'Date Report',
      'Certificate',
      'Note',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    // Style header (row 1)
    $sheet->getStyle('A1:R1')->applyFromArray([
      'font' => ['bold' => true],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'ADD8E6'],
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      ],
    ]);

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);   // No
    $sheet->getColumnDimension('B')->setWidth(12);  // Event Code
    $sheet->getColumnDimension('C')->setWidth(35);  // Training Name
    $sheet->getColumnDimension('D')->setWidth(12);  // Group Comp
    $sheet->getColumnDimension('E')->setWidth(10);  // Type
    $sheet->getColumnDimension('F')->setWidth(20);  // Instructor
    $sheet->getColumnDimension('G')->setWidth(25);  // Venue
    $sheet->getColumnDimension('H')->setWidth(12);  // NRP
    $sheet->getColumnDimension('I')->setWidth(20);  // Name
    $sheet->getColumnDimension('J')->setWidth(10);  // Section
    $sheet->getColumnDimension('K')->setWidth(25);  // Period
    $sheet->getColumnDimension('L')->setWidth(18);  // Duration
    $sheet->getColumnDimension('M')->setWidth(12);  // Theory Score
    $sheet->getColumnDimension('N')->setWidth(12);  // Practical Score
    $sheet->getColumnDimension('O')->setWidth(10);  // Remarks
    $sheet->getColumnDimension('P')->setWidth(12);  // Date Report
    $sheet->getColumnDimension('Q')->setWidth(12);  // Certificate
    $sheet->getColumnDimension('R')->setWidth(20);  // Note

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

    // Left align text columns
    $lastRow = $sheet->getHighestRow();
    $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('R2:R' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
  }
}
