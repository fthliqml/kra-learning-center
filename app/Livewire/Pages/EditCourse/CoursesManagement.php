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

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'title', 'label' => 'Title', 'class' => 'w-[300px]'],
            ['key' => 'group_comp', 'label' => 'Group Comp', 'class' => '!text-center'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function courses()
    {
        $query = Course::with('training')
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filter, function ($q) {
                $q->whereHas('training', function ($t) {
                    $t->where('group_comp', $this->filter);
                });
            })
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
    }

    public function render()
    {
        return view('pages.edit-course.courses-management', [
            'courses' => $this->courses(),
            'headers' => $this->headers(),
        ]);
    }
}
