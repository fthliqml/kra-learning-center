<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Courses extends Component
{
    use WithPagination;

    // Filters/search bound from the view
    public string $search = '';
    public ?string $filter = null; // group_comp value

    // Options for the filter dropdown
    public $groupOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    // Reset to the first page when search/filter changes
    public function updated($property): void
    {
        if (!is_array($property) && $property !== '') {
            $this->resetPage();
        }
    }

    #[Computed]
    public function courses()
    {
        $query = Course::query()
            ->with('training')
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filter, fn($q) => $q->where('group_comp', $this->filter))
            ->orderBy('created_at', 'desc');

        return $query->paginate(12)->onEachSide(1);
    }

    #[Computed]
    public function resultsText(): string
    {
        $courses = $this->courses();

        // Support both paginator and collection just in case of future changes
        $hasPaginator = is_object($courses) && method_exists($courses, 'total');
        $total = $hasPaginator ? $courses->total() : (is_countable($courses) ? count($courses) : 0);
        $from = $hasPaginator ? ($total ? ($courses->firstItem() ?? 0) : 0) : ($total ? 1 : 0);
        $to = $hasPaginator ? ($total ? ($courses->lastItem() ?? 0) : 0) : $total;

        return $total ? "Showing {$from}–{$to} of {$total} results" : '';
    }

    public function render()
    {
        return view('pages.courses.courses', [
            'courses' => $this->courses(),
            'resultsText' => $this->resultsText(),
        ]);
    }
}
