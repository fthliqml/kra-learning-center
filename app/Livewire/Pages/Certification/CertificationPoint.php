<?php

namespace App\Livewire\Pages\Certification;

use Livewire\Component;
use Livewire\WithPagination;

class CertificationPoint extends Component
{
    use WithPagination;

    public $search = '';

    // Dummy data untuk simulasi
    protected $dummyData = [
        ['nrp' => 'NRP001', 'name' => 'Andi Wijaya', 'section' => 'Sub-Assy 3 and 4', 'point' => 85],
        ['nrp' => 'NRP002', 'name' => 'Budi Santoso', 'section' => 'Main Disassy Power Train', 'point' => 92],
        ['nrp' => 'NRP003', 'name' => 'Citra Dewi', 'section' => 'Sub-Assy 3 and 4', 'point' => 78],
        ['nrp' => 'NRP004', 'name' => 'Dani Pratama', 'section' => 'Final Assy and Inspection', 'point' => 88],
        ['nrp' => 'NRP005', 'name' => 'Eka Putri', 'section' => 'Main Disassy Power Train', 'point' => 95],
        ['nrp' => 'NRP006', 'name' => 'Fajar Ramadan', 'section' => 'Sub-Assy 1 and 2', 'point' => 81],
        ['nrp' => 'NRP007', 'name' => 'Gita Sari', 'section' => 'Sub-Assy 3 and 4', 'point' => 89],
        ['nrp' => 'NRP008', 'name' => 'Hendra Kusuma', 'section' => 'Main Disassy Power Train', 'point' => 76],
        ['nrp' => 'NRP009', 'name' => 'Indah Permata', 'section' => 'Final Assy and Inspection', 'point' => 93],
        ['nrp' => 'NRP010', 'name' => 'Joko Widodo', 'section' => 'Sub-Assy 1 and 2', 'point' => 87],
        ['nrp' => 'NRP011', 'name' => 'Kartika Sari', 'section' => 'Main Disassy Power Train', 'point' => 84],
        ['nrp' => 'NRP012', 'name' => 'Lukman Hakim', 'section' => 'Sub-Assy 3 and 4', 'point' => 91],
        ['nrp' => 'NRP013', 'name' => 'Maya Anggraini', 'section' => 'Final Assy and Inspection', 'point' => 79],
        ['nrp' => 'NRP014', 'name' => 'Nugroho Adi', 'section' => 'Sub-Assy 1 and 2', 'point' => 86],
        ['nrp' => 'NRP015', 'name' => 'Olivia Tan', 'section' => 'Main Disassy Power Train', 'point' => 94],
    ];

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12 md:w-[8%]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => 'md:w-[15%]'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'md:w-[30%]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center md:w-[20%]'],
            ['key' => 'point', 'label' => 'Point', 'class' => '!text-center md:w-[15%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center md:w-[12%]'],
        ];
    }

    public function certificationPoints()
    {
        // Filter dummy data berdasarkan search
        $filtered = collect($this->dummyData);

        if ($this->search) {
            $term = strtolower($this->search);
            $filtered = $filtered->filter(function ($item) use ($term) {
                return str_contains(strtolower($item['nrp']), $term) ||
                    str_contains(strtolower($item['name']), $term) ||
                    str_contains(strtolower($item['section']), $term) ||
                    str_contains((string) $item['point'], $term);
            });
        }

        // Manual pagination untuk dummy data
        $perPage = 10;
        $currentPage = $this->getPage();
        $total = $filtered->count();

        $items = $filtered
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        // Create paginator manually
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        return $paginator->through(function ($item, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $item['no'] = $start + $index;
            return (object) $item;
        });
    }

    public function render()
    {
        return view('pages.certification.certification-point', [
            'headers' => $this->headers(),
            'certificationPoints' => $this->certificationPoints(),
        ]);
    }
}
