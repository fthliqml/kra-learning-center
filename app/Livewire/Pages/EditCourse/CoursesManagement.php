<?php

namespace App\Livewire\Pages\EditCourse;

use App\Models\Course;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class CoursesManagement extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public ?string $filter = null; // filter by related training group_comp
    public ?string $filterGroup = null; // new group filter (replacing $filter gradually)
    public ?string $filterStatus = null; // status filter
    public bool $showFilterModal = false;
    public $currentPage;

    public $groupOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public function updated($property): void
    {
        if (!is_array($property) && $property !== '') {
            $this->resetPage();
        }
    }

    public function openFilters(): void
    {
        $this->showFilterModal = true;
    }

    public function closeFilters(): void
    {
        // TODO : when closing filter, don't apply changes
        $this->showFilterModal = false;
    }

    public function clearFilters(): void
    {
        $this->filterGroup = null;
        $this->filterStatus = null;
        $this->filter = null; // legacy
        $this->resetPage();
        $this->showFilterModal = false;
    }

    public function applyFilters(): void
    {
        // Nothing special here; reactive properties already used in query
        $this->filter = $this->filterGroup; // keep backward compatibility if blade still references $filter
        $this->resetPage();
        $this->showFilterModal = false;
    }


    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'title', 'label' => 'Title', 'class' => 'w-[300px]'],
            ['key' => 'competency', 'label' => 'Competency', 'class' => '!text-center w-[200px]'],
            ['key' => 'group_comp', 'label' => 'Group Comp', 'class' => '!text-center'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function updatingPage($value)
    {
        $this->currentPage = $value; // set properti publik saat pagination berubah
    }

    public function courses()
    {
        $query = Course::query()
            ->with('competency:id,name,type')
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterGroup ?? $this->filter, function ($q, $group) {
                $value = trim((string) $group);
                if ($value !== '') {
                    $q->whereHas('competency', fn($qq) => $qq->where('type', $value));
                }
            })
            ->when($this->filterStatus, fn($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate(10)->onEachSide(1);

        return $paginator->through(function ($course, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $course->no = $start + $index;
            return $course;
        });
    }

    #[On('deleteCourse')]
    public function deleteCourse($id): void
    {
        Course::findOrFail($id)->delete();
        $this->error('Course deleted', position: 'toast-top toast-center');
        // Close confirm dialog and stop spinner
        $this->dispatch('confirm-done');
    }

    public function mount()
    {
        $this->currentPage = 1; // default
    }

    public function render()
    {
        return view('pages.edit-course.courses-management', [
            'courses' => $this->courses(),
            'headers' => $this->headers(),
        ]);
    }
}
