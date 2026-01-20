<?php

namespace App\Exports;

use App\Models\Certification;
use App\Models\CertificationParticipant;
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
                $term = '%' . $this->search . '%';

                $q->where(function ($inner) use ($term) {
                    $inner
                        ->whereHas('employee', function ($query) use ($term) {
                            $query->where('name', 'like', $term)->orWhere('nrp', 'like', $term);
                        })
                        ->orWhereHas('certification', fn($query) => $query->where('name', 'like', $term))
                        ->orWhereHas('certification.certificationModule', fn($query) => $query->where('module_title', 'like', $term));
                });
            });

        return $query
            ->orderByDesc(
                Certification::select('approved_at')
                    ->whereColumn('certifications.id', 'certification_participants.certification_id')
                    ->limit(1)
            )
            ->orderByDesc('certification_participants.id')
            ->get();
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

        // Completion date
        $completionDate = $certification?->approved_at
            ? Carbon::parse($certification->approved_at)->format('d-m-Y')
            : '-';

        return [
            $this->rowNumber,
            $employee?->nrp ?? ($employee?->NRP ?? '-'),
            $employee?->name ?? '-',
            $employee?->section ?? '-',
            $theoryScore !== null ? number_format($theoryScore, 1) : '-',
            $practicalScore !== null ? number_format($practicalScore, 1) : '-',
            ucfirst($remarks),
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
            'Theory Score',
            'Practical Score',
            'Remarks',
            'Note',
            'Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header (row 1) - 9 columns (A-I)
        $sheet->getStyle('A1:I1')->applyFromArray([
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
        $sheet->getColumnDimension('E')->setWidth(14);  // Theory Score
        $sheet->getColumnDimension('F')->setWidth(14);  // Practical Score
        $sheet->getColumnDimension('G')->setWidth(12);  // Remarks
        $sheet->getColumnDimension('H')->setWidth(20);  // Note
        $sheet->getColumnDimension('I')->setWidth(14);  // Date

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
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    }
}
