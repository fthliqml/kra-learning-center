<?php

namespace App\Livewire\Pages\Training;

use App\Models\Training;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class Approval extends Component
{
    use WithPagination, Toast;

    public $modal = false;
    public $selectedId = null;
    public $activeTab = 'information';

    public $search = '';
    public $filter = 'All';

    public array $formData = [
        'training_name' => '',
        'type' => '',
        'created_at' => '',
    ];

    public $groupOptions = [
        ['value' => 'Pending', 'label' => 'Pending'],
        ['value' => 'Approved', 'label' => 'Approved'],
        ['value' => 'Rejected', 'label' => 'Rejected'],
    ];

    public function mount(): void
    {
        // No need for user searchable since we don't have create mode
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12 !md:w-[8%]'],
            ['key' => 'training_name', 'label' => 'Training Name', 'class' => '!md:w-[50%]'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-center !md:w-[12%]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center !md:w-[14%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center !md:w-[10%]'],
        ];
    }

    public function participantHeaders(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-[60px]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => 'w-[120px]'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-[200px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center w-[100px]'],
            ['key' => 'score', 'label' => 'Score', 'class' => '!text-center w-[80px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center w-[100px]'],
        ];
    }

    public function getParticipantsProperty()
    {
        if (!$this->selectedId) {
            return collect();
        }

        $training = Training::with([
            'attendances.employee',
            'assessments.employee',
        ])->find($this->selectedId);

        if (!$training) {
            return collect();
        }

        // Get unique participants from attendances
        $participants = $training->attendances->unique('employee_id')->map(function ($attendance, $index) use ($training) {
            $employee = $attendance->employee;

            // Get assessment for this employee
            $assessment = $training->assessments->firstWhere('employee_id', $employee->id);

            // Determine status
            $status = $assessment ? $assessment->status : 'pending';
            $score = $assessment ? $assessment->score : null;

            return (object) [
                'no' => $index + 1,
                'nrp' => $employee->nrp ?? '-',
                'name' => $employee->name ?? '-',
                'section' => $employee->section ?? '-',
                'score' => $score !== null ? number_format($score, 1) : '-',
                'status' => $status,
                'score_raw' => $score,
            ];
        });

        return $participants->values();
    }

    public function approvals()
    {
        $query = Training::query()
            ->whereIn('status', ['done', 'approved', 'rejected']);

        // Filter by search
        if ($this->search) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%")
                    ->orWhere('group_comp', 'like', "%{$term}%");
            });
        }

        // Filter by display status (done = pending for display)
        if ($this->filter && strtolower($this->filter) !== 'all') {
            $filterStatus = strtolower($this->filter);

            if ($filterStatus === 'pending') {
                $query->where('status', 'done');
            } else {
                $query->where('status', $filterStatus);
            }
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'done' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(10)
            ->through(function ($training) {
                // Map status: done -> pending for display
                $displayStatus = $training->status === 'done' ? 'pending' : $training->status;

                return (object) [
                    'id' => $training->id,
                    'training_name' => $training->name,
                    'type' => $training->type ?? '-',
                    'group_comp' => $training->group_comp ?? '-',
                    'start_date' => $training->start_date,
                    'end_date' => $training->end_date,
                    'status' => $displayStatus,
                    'actual_status' => $training->status, // Store actual status for updates
                ];
            });
    }

    public function render()
    {
        return view('pages.training.training-approval', [
            'headers' => $this->headers(),
            'approvals' => $this->approvals(),
        ]);
    }

    public function openDetailModal(int $id): void
    {
        $training = Training::find($id);

        if (!$training) {
            return;
        }

        // Map status: done -> pending for display
        $displayStatus = $training->status === 'done' ? 'pending' : $training->status;

        $this->selectedId = $training->id;
        $this->activeTab = 'information'; // Reset to information tab
        $this->formData = [
            'training_name' => $training->name,
            'type' => $training->type ?? '-',
            'group_comp' => $training->group_comp ?? '-',
            'start_date' => $training->start_date ? $training->start_date->format('d F Y') : '-',
            'end_date' => $training->end_date ? $training->end_date->format('d F Y') : '-',
            'created_at' => $training->created_at->format('d F Y'),
            'status' => $displayStatus,
            'actual_status' => $training->status,
        ];
        $this->modal = true;
        $this->resetValidation();
    }

    /**
     * Determine if the current authenticated user can moderate (approve/reject)
     */
    protected function canModerate(): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;
        return strtolower(trim($user->role ?? '')) === 'leader' && strtolower(trim($user->section ?? '')) === 'lid';
    }

    /** Approve selected request */
    public function approve(): void
    {
        if (!$this->selectedId) {
            return;
        }
        if (!$this->canModerate()) {
            $this->error('Only LID leader can approve.', position: 'toast-top toast-center');
            return;
        }

        $training = Training::find($this->selectedId);

        if (!$training) {
            $this->error('Training not found.', position: 'toast-top toast-center');
            return;
        }

        // Update status to approved
        $training->update([
            'status' => 'approved',
        ]);

        $this->formData['status'] = 'approved';
        $this->success('Training approved successfully', position: 'toast-top toast-center');

        $this->modal = false;
    }

    /** Reject selected request */
    public function reject(): void
    {
        if (!$this->selectedId) {
            return;
        }
        if (!$this->canModerate()) {
            $this->error('Only LID leader can reject.', position: 'toast-top toast-center');
            return;
        }

        $training = Training::find($this->selectedId);

        if (!$training) {
            $this->error('Training not found.', position: 'toast-top toast-center');
            return;
        }

        // Update status to rejected
        $training->update(['status' => 'rejected']);

        $this->formData['status'] = 'rejected';
        $this->error('Training rejected', position: 'toast-top toast-center');

        $this->modal = false;
    }
}
