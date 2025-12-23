<?php

namespace App\Exports;

use App\Models\CertificationModule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CertificationModuleExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected int $rowNumber = 0;

    /**
     * @return Collection
     */
    public function collection()
    {
        return CertificationModule::with('competency')->orderBy('code')->get();
    }

    public function headings(): array
    {
        return [
            'No',
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
            'Active',
            'Created At',
        ];
    }

    public function map($m): array
    {
        $this->rowNumber++;
        $competencyLabel = $m->competency
            ? trim((string) $m->competency->code . ' - ' . (string) $m->competency->name)
            : '-';
        return [
            $this->rowNumber,
            $m->code,
            $m->module_title,
            $competencyLabel,
            $m->level,
            $m->group_certification,
            (int) $m->points_per_module,
            (float) $m->new_gex,
            (int) $m->duration,
            (float) $m->theory_passing_score,
            (float) $m->practical_passing_score,
            $m->major_component,
            $m->mach_model,
            $m->is_active ? 'Yes' : 'No',
            optional($m->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'O'; // 15 columns
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

        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'alignment' => [
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
    }
}
