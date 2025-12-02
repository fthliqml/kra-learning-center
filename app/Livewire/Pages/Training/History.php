<?php

namespace App\Livewire\Pages\Training;

use App\Models\TrainingAttendance;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class History extends Component
{
    use Toast, WithPagination;

    public $search = '';
    public $filter = null;

    public $typeOptions = [
        ['value' => 'IN', 'label' => 'In-house'],
        ['value' => 'OUT', 'label' => 'Out-house'],
        ['value' => 'LMS', 'label' => 'LMS'],
    ];

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function headers()
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center !p-4'],
            ['key' => 'training_name', 'label' => 'Training Name', 'class' => 'w-[300px]'],
            ['key' => 'type', 'label' => 'Type', 'class' => '!text-center w-[150px]'],
            ['key' => 'group_comp', 'label' => 'Group Comp', 'class' => '!text-center'],
            ['key' => 'instructor', 'label' => 'Instructor', 'class' => '!text-center'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
            ['key' => 'certificate', 'label' => 'Certificate', 'class' => '!text-center'],
        ];
    }

    public function histories()
    {
        $userId = Auth::id();

        $query = TrainingAttendance::query()
            ->with([
                'session.training.course',
                'session.training.sessions.trainer.user', // untuk ambil instructor
                'session.training.assessments' => function ($q) use ($userId) {
                    $q->where('employee_id', $userId);
                }
            ])
            ->where('employee_id', $userId)
            ->whereHas('session.training', function ($q) {
                $q->where('status', 'done');
            })
            ->when($this->search, function ($q) {
                $q->whereHas('session.training', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filter, function ($q) {
                $q->whereHas('session.training', function ($query) {
                    $query->where('type', $this->filter);
                });
            })
            ->orderBy('created_at', 'desc');

        // Group by training_id untuk menghindari duplikasi
        $attendances = $query->get()->groupBy('session.training_id');

        // Convert ke collection dengan data yang sudah di-aggregate
        $items = $attendances->map(function ($attendanceGroup) {
            $training = $attendanceGroup->first()->session->training;

            // Ambil assessment jika ada
            $assessment = $training->assessments->first();
            $status = $assessment ? $assessment->status : null;

            // Ambil instructor (trainer) dari session pertama training
            $firstSession = $training->sessions->first();
            $instructor = $firstSession && $firstSession->trainer
                ? ($firstSession->trainer->name ?? $firstSession->trainer->user->name ?? '-')
                : '-';

            // Format type label
            $typeLabel = match ($training->type) {
                'IN' => 'In-House',
                'OUT' => 'Out-House',
                'LMS' => 'LMS',
                default => $training->type
            };

            return (object) [
                'id' => $training->id,
                'training_name' => $training->name,
                'type' => $typeLabel,
                'group_comp' => $training->group_comp,
                'instructor' => $instructor,
                'status' => $status,
                'certificate' => 'View Certificate', // hardcode untuk link
                'start_date' => $training->start_date,
            ];
        })->sortByDesc('start_date')->values();

        // Manual pagination
        $perPage = 10;
        $currentPage = $this->getPage();
        $total = $items->count();

        $paginated = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        return $paginator->onEachSide(1)->through(function ($item, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $item->no = $start + $index;
            return $item;
        });
    }

    public function render()
    {
        return view('pages.training.training-history', [
            'histories' => $this->histories(),
            'headers' => $this->headers()
        ]);
    }
}
