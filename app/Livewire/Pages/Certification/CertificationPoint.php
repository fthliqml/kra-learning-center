<?php

namespace App\Livewire\Pages\Certification;

use App\Exports\CertificationPointExport;
use App\Models\CertificationPoint as CertificationPointModel;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class CertificationPoint extends Component
{
  use WithPagination;

  public $search = '';
  public $sortOrder = 'desc'; // 'desc' = tertinggi, 'asc' = terendah

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
      ['key' => 'section', 'label' => 'Section', 'class' => '!text-center md:w-[25%]'],
      ['key' => 'point', 'label' => 'Point', 'class' => '!text-center md:w-[22%]'],
    ];
  }

  public function sortOptions(): array
  {
    return [
      ['value' => 'desc', 'label' => 'Highest First'],
      ['value' => 'asc', 'label' => 'Lowest First'],
    ];
  }

  public function certificationPoints()
  {
    $query = CertificationPointModel::with('employee');

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

    $paginated = $query->paginate(10);

    return $paginated->through(function ($item, $index) use ($paginated) {
      $start = $paginated->firstItem() ?? 0;
      return (object) [
        'no' => $start + $index,
        'nrp' => $item->employee?->NRP ?? '-',
        'name' => $item->employee?->name ?? '-',
        'section' => $item->employee?->section ?? '-',
        'point' => $item->total_points,
      ];
    });
  }

  public function export()
  {
    return Excel::download(
      new CertificationPointExport($this->search, $this->sortOrder),
      'certification_points_' . Carbon::now()->format('Y-m-d') . '.xlsx'
    );
  }

  public function render()
  {
    return view('pages.certification.certification-point', [
      'headers' => $this->headers(),
      'certificationPoints' => $this->certificationPoints(),
      'sortOptions' => $this->sortOptions(),
    ]);
  }
}
