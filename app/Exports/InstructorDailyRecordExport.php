<?php

namespace App\Exports;

use App\Models\InstructorDailyRecord;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstructorDailyRecordExport implements FromCollection, WithHeadings, WithStyles, WithMapping
{
  protected $dateFrom;
  protected $dateTo;
  protected $search;
  protected $instructorId;
  protected $rowNumber = 0;

  public function __construct($dateFrom = null, $dateTo = null, $search = '', $instructorId = null)
  {
    $this->dateFrom = $dateFrom;
    $this->dateTo = $dateTo;
    $this->search = $search;
    $this->instructorId = $instructorId;
  }

  public function collection()
  {
    $query = InstructorDailyRecord::with('instructor')
      ->when($this->instructorId, fn($q) => $q->where('instructor_id', $this->instructorId))
      ->when($this->dateFrom, fn($q) => $q->whereDate('date', '>=', $this->dateFrom))
      ->when($this->dateTo, fn($q) => $q->whereDate('date', '<=', $this->dateTo))
      ->when($this->search, function ($q) {
        $q->where(function ($query) {
          $query->where('code', 'like', '%' . $this->search . '%')
            ->orWhere('activity', 'like', '%' . $this->search . '%')
            ->orWhere('remarks', 'like', '%' . $this->search . '%')
            ->orWhereHas('instructor', function ($iq) {
              $iq->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('nrp', 'like', '%' . $this->search . '%');
            });
        });
      })
      ->orderBy('date', 'desc')
      ->orderBy('id', 'desc');

    return $query->get();
  }

  public function map($record): array
  {
    $this->rowNumber++;

    return [
      $this->rowNumber,
      $record->instructor->nrp ?? '-',
      $record->instructor->name ?? '-',
      $record->date ? Carbon::parse($record->date)->format('d-m-Y') : '-',
      $record->code,
      $record->group,
      $record->activity,
      $record->remarks ?? '-',
      number_format($record->hour, 1),
    ];
  }

  public function headings(): array
  {
    return [
      'No',
      'NRP',
      'Name',
      'Date',
      'Code',
      'Group',
      'Activity',
      'Remarks',
      'Hour',
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    // Auto-size columns
    foreach (range('A', 'I') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return [
      // Header row styling
      1 => [
        'font' => [
          'bold' => true,
          'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['rgb' => '0F52BA'], // Primary blue
        ],
        'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
      ],
    ];
  }
}
