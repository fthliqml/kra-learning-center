<?php

namespace App\Livewire\Pages\Certification;

use App\Models\CertificationModule as CertificationModuleModel;
use Livewire\Component;
use Livewire\WithPagination;

class CertificationModule extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = '';

    public $groupOptions = [
        ['value' => 'Basic', 'label' => 'Basic'],
        ['value' => 'Intermediate', 'label' => 'Intermediate'],
        ['value' => 'Advanced', 'label' => 'Advanced'],
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
            ['key' => 'code', 'label' => 'Code', 'class' => 'md:w-[5%]'],
            ['key' => 'level', 'label' => 'Level', 'class' => '!text-center md:w-[16%]'],
            ['key' => 'competency', 'label' => 'Competency', 'class' => 'md:w-[18%]'],
            ['key' => 'point', 'label' => 'Point', 'class' => '!text-center md:w-[10%]'],
            ['key' => 'new_gex', 'label' => 'New GEX', 'class' => '!text-center md:w-[12%]'],
            ['key' => 'duration', 'label' => 'Duration', 'class' => '!text-center md:w-[12%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center md:w-[14%]'],
        ];
    }

    public function modules()
    {
        $query = CertificationModuleModel::query()
            ->when($this->filter !== '', function ($q) {
                $q->where('level', $this->filter);
            })
            ->when(trim($this->search) !== '', function ($q) {
                $s = trim($this->search);
                $term = "%{$s}%";
                $q->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('competency', 'like', $term)
                        ->orWhere('level', 'like', $term);
                });
            })
            ->orderBy('code');

        $paginator = $query->paginate(10);

        return $paginator->through(function ($m, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $m->no = $start + $index;
            return $m;
        });
    }

    public function render()
    {
        return view('pages.certification.certification-module', [
            'headers' => $this->headers(),
            'modules' => $this->modules(),
        ]);
    }
}
