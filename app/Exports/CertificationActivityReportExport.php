<?php

namespace App\Exports;

use App\Models\CertificationParticipant;
use App\Models\CertificationPoint;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CertificationActivityReportExport implements FromCollection, WithHeadings, WithStyles, WithMapping
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
    $query = CertificationParticipant::with([
      'certification.certificationModule',
      'employee',
      'scores.session',
    ])
      ->whereHas('certification', function ($q) {
        $q->where('status', 'completed')
          ->when($this->dateFrom, fn($query) => $query->whereDate('approved_at', '>=', $this->dateFrom))
          ->when($this->dateTo, fn($query) => $query->whereDate('approved_at', '<=', $this->dateTo));
      })
      ->when($this->search, function ($q) {
        $q->whereHas('employee', fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
          ->orWhereHas('certification', fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
          ->orWhereHas('certification.certificationModule', fn($query) => $query->where('competency', 'like', '%' . $this->search . '%'));
      });

    return $query->get();
  }

  public function map($participant): array
  {
    $this->rowNumber++;
    $certification = $participant->certification;
    $module = $certification?->certificationModule;
    $employee = $participant->employee;

    // Get theory and practical scores from certification_scores
    $theoryScore = null;
    $practicalScore = null;

    foreach ($participant->scores as $score) {
      $sessionType = $score->session?->type;
      if ($sessionType === 'theory') {
        $theoryScore = $score->score;
      } elseif ($sessionType === 'practical') {
        $practicalScore = $score->score;
      }
    }

    // Remarks from final_status
    $remarks = $participant->final_status ?? 'pending';

    // Earned points: if passed, get from module's points_per_module
    $earnedPoint = $remarks === 'passed' ? ($module?->points_per_module ?? 0) : 0;

    // Total points: get accumulated points from certification_points table
    $certPoint = CertificationPoint::where('employee_id', $employee?->id)->first();
    $totalPoint = $certPoint?->total_points ?? 0;

    // Completion date
    $completionDate = $certification?->approved_at
      ? Carbon::parse($certification->approved_at)->format('d-m-Y')
      : '-';

    return [
      $this->rowNumber,
      $employee?->NRP ?? '-',
      $employee?->name ?? '-',
      $employee?->section ?? '-',
      $module?->competency ?? '-',
      $theoryScore !== null ? number_format($theoryScore, 1) : '-',
      $practicalScore !== null ? number_format($practicalScore, 1) : '-',
      ucfirst($remarks),
      $earnedPoint,
      $totalPoint,
      '-', // Note
      $completionDate,
    ];
  }

  public function headings(): array
  {
    return [
      'No',
      'NRP',
      'Name',
      'Section',
      'Competency',
      'Theory Score',
      'Practical Score',
      'Remarks',
      'Earned Point',
      'Total Point',
      'Note',
      'Date',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    // Style header (row 1)
    $sheet->getStyle('A1:L1')->applyFromArray([
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
    $sheet->getColumnDimension('B')->setWidth(12);  // NRP
    $sheet->getColumnDimension('C')->setWidth(25);  // Name
    $sheet->getColumnDimension('D')->setWidth(12);  // Section
    $sheet->getColumnDimension('E')->setWidth(30);  // Competency
    $sheet->getColumnDimension('F')->setWidth(14);  // Theory Score
    $sheet->getColumnDimension('G')->setWidth(14);  // Practical Score
    $sheet->getColumnDimension('H')->setWidth(12);  // Remarks
    $sheet->getColumnDimension('I')->setWidth(12);  // Earned Point
    $sheet->getColumnDimension('J')->setWidth(12);  // Total Point
    $sheet->getColumnDimension('K')->setWidth(20);  // Note
    $sheet->getColumnDimension('L')->setWidth(14);  // Date

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
    $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('K2:K' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
  }
}
