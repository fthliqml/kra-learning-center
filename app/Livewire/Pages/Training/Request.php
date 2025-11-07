<?php

namespace App\Livewire\Pages\Training;

use App\Models\Request as TrainingRequestModel;
use Livewire\Component;
use Livewire\WithPagination;

class Request extends Component
{
    use WithPagination;

    public $search = '';
    public $filter = 'All';

    public $groupOptions = [
        ['value' => 'Pending', 'label' => 'Pending'],
        ['value' => 'Approved', 'label' => 'Approved'],
        ['value' => 'Rejected', 'label' => 'Rejected'],
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
            ['key' => 'user', 'label' => 'Name', 'class' => 'md:w-[40%]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center md:w-[18%]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center md:w-[16%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center md:w-[10%]'],
        ];
    }

    public function requests()
    {
        $query = TrainingRequestModel::query()
            ->leftJoin('users', 'training_requests.created_by', '=', 'users.id')
            ->leftJoin('users as u2', 'training_requests.user_id', '=', 'u2.id')
            ->when($this->filter && strtolower($this->filter) !== 'all', function ($q) {
                $q->where('training_requests.status', strtolower($this->filter));
            })
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('training_requests.competency', 'like', $term)
                        ->orWhere('training_requests.reason', 'like', $term)
                        ->orWhere('users.name', 'like', $term)
                        ->orWhere('u2.name', 'like', $term)
                        ->orWhere('u2.section', 'like', $term);
                });
            })
            ->orderBy('training_requests.created_at', 'desc')
            ->select('training_requests.*', 'users.name as created_by_name', 'u2.name as user_name', 'u2.section as section');

        $paginator = $query->paginate(10);

        return $paginator->through(function ($req, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $req->no = $start + $index;
            return $req;
        });
    }

    public function render()
    {
        return view('pages.training.training-request', [
            'headers' => $this->headers(),
            'requests' => $this->requests(),
        ]);
    }
}
