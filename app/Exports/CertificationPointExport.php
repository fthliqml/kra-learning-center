<?php

namespace App\Exports;

use App\Models\CertificationPoint;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CertificationPointExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
  protected $search;
  protected $sortOrder;
  protected $rowNumber = 0;

  public function __construct($search = '', $sortOrder = 'desc')
  {
    $this->search = $search;
    $this->sortOrder = $sortOrder;
  }

  public function collection()
  {
    $query = CertificationPoint::with('employee');

    // Sort order
    if ($this->sortOrder === 'asc') {
      $query->orderBy('total_points');
    } else {
      $query->orderByDesc('total_points');
    }

    // Filter by search
    if ($this->search) {
      $term = $this->search;
      $query->whereHas('employee', function ($q) use ($term) {
        $q->where('name', 'like', '%' . $term . '%')
          ->orWhere('NRP', 'like', '%' . $term . '%')
          ->orWhere('section', 'like', '%' . $term . '%');
      });
    }

    return $query->get();
  }

  public function map($item): array
  {
    $this->rowNumber++;

    return [
      $this->rowNumber,
      $item->employee?->NRP ?? '-',
      $item->employee?->name ?? '-',
      $item->employee?->section ?? '-',
      $item->total_points,
    ];
  }

  public function headings(): array
  {
    return [
      'No',
      'NRP',
      'Name',
      'Section',
      'Point',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    // Style header (row 1)
    $sheet->getStyle('A1:E1')->applyFromArray([
      'font' => ['bold' => true],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'ADD8E6'],
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
      ],
    ]);

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);   // No
    $sheet->getColumnDimension('B')->setWidth(15);  // NRP
    $sheet->getColumnDimension('C')->setWidth(30);  // Name
    $sheet->getColumnDimension('D')->setWidth(25);  // Section
    $sheet->getColumnDimension('E')->setWidth(10);  // Point

    // Style all cells
    $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
      'alignment' => [
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
          'color' => ['rgb' => '000000'],
        ],
      ],
    ]);

    // Center align specific columns
    $lastRow = $sheet->getHighestRow();
    $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  }
}
