<?php

namespace App\Livewire\Pages\Certification;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class CertificationApproval extends Component
{
    use WithPagination, Toast;

    public $modal = false;
    public $selectedId = null;

    public $search = '';
    public $filter = 'All';

    public array $formData = [
        'certification_name' => '',
        'competency' => '',
        'reason' => '',
    ];

    public $groupOptions = [
        ['value' => 'Pending', 'label' => 'Pending'],
        ['value' => 'Approved', 'label' => 'Approved'],
        ['value' => 'Rejected', 'label' => 'Rejected'],
    ];

    // Dummy data untuk simulasi
    protected $dummyData = [
        ['id' => 1, 'certification_name' => 'Certified Welding Inspector', 'competency' => 'Welding Advanced', 'reason' => 'Need for new project', 'status' => 'pending', 'date' => '2024-11-01', 'created_at' => '2024-11-01'],
        ['id' => 2, 'certification_name' => 'ISO 9001:2015 Lead Auditor', 'competency' => 'Quality Control', 'reason' => 'Skill improvement', 'status' => 'approved', 'date' => '2024-11-02', 'created_at' => '2024-11-02'],
        ['id' => 3, 'certification_name' => 'NEBOSH International General Certificate', 'competency' => 'Safety Training', 'reason' => 'Mandatory certification', 'status' => 'pending', 'date' => '2024-11-03', 'created_at' => '2024-11-03'],
        ['id' => 4, 'certification_name' => 'Certified Maintenance & Reliability Professional', 'competency' => 'Machine Operation', 'reason' => 'Job rotation', 'status' => 'rejected', 'date' => '2024-11-04', 'created_at' => '2024-11-04'],
        ['id' => 5, 'certification_name' => 'Lean Six Sigma Black Belt', 'competency' => 'Lean Manufacturing', 'reason' => 'Process improvement', 'status' => 'pending', 'date' => '2024-11-05', 'created_at' => '2024-11-05'],
        ['id' => 6, 'certification_name' => 'Certified Forklift Operator', 'competency' => 'Forklift Operation', 'reason' => 'License renewal', 'status' => 'approved', 'date' => '2024-11-06', 'created_at' => '2024-11-06'],
        ['id' => 7, 'certification_name' => 'ISO 9001 Internal Auditor', 'competency' => 'ISO 9001 Auditor', 'reason' => 'Career development', 'status' => 'pending', 'date' => '2024-11-07', 'created_at' => '2024-11-07'],
        ['id' => 8, 'certification_name' => 'Certified Electrical Safety Professional', 'competency' => 'Electrical Systems', 'reason' => 'Technical requirement', 'status' => 'approved', 'date' => '2024-11-08', 'created_at' => '2024-11-08'],
        ['id' => 9, 'certification_name' => 'Project Management Professional (PMP)', 'competency' => 'Project Management', 'reason' => 'Promotion preparation', 'status' => 'pending', 'date' => '2024-11-09', 'created_at' => '2024-11-09'],
        ['id' => 10, 'certification_name' => 'Six Sigma Green Belt', 'competency' => 'Six Sigma Green Belt', 'reason' => 'Quality initiative', 'status' => 'rejected', 'date' => '2024-11-10', 'created_at' => '2024-11-10'],
        ['id' => 11, 'certification_name' => 'AutoCAD Certified Professional', 'competency' => 'AutoCAD Professional', 'reason' => 'Design work', 'status' => 'pending', 'date' => '2024-11-11', 'created_at' => '2024-11-11'],
        ['id' => 12, 'certification_name' => 'Certified Hydraulic Specialist', 'competency' => 'Hydraulic Systems', 'reason' => 'Maintenance team', 'status' => 'approved', 'date' => '2024-11-12', 'created_at' => '2024-11-12'],
        ['id' => 13, 'certification_name' => 'Statistical Process Control Practitioner', 'competency' => 'Statistical Process Control', 'reason' => 'Data analysis', 'status' => 'pending', 'date' => '2024-11-13', 'created_at' => '2024-11-13'],
        ['id' => 14, 'certification_name' => 'Certified Leadership Development Program', 'competency' => 'Leadership Development', 'reason' => 'Team leader candidate', 'status' => 'approved', 'date' => '2024-11-14', 'created_at' => '2024-11-14'],
        ['id' => 15, 'certification_name' => 'CNC Programming and Operations', 'competency' => 'CNC Programming', 'reason' => 'New machine introduction', 'status' => 'pending', 'date' => '2024-11-15', 'created_at' => '2024-11-15'],
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
            ['key' => 'certification_name', 'label' => 'Certification Name', 'class' => '!md:w-[50%]'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-center !md:w-[12%]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center !md:w-[14%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center !md:w-[10%]'],
        ];
    }

    public function approvals()
    {
        $filtered = collect($this->dummyData);

        // Filter by status
        if ($this->filter && strtolower($this->filter) !== 'all') {
            $filtered = $filtered->filter(function ($item) {
                return strtolower($item['status']) === strtolower($this->filter);
            });
        }

        // Filter by search
        if ($this->search) {
            $term = strtolower($this->search);
            $filtered = $filtered->filter(function ($item) use ($term) {
                return str_contains(strtolower($item['certification_name']), $term) ||
                    str_contains(strtolower($item['competency']), $term) ||
                    str_contains(strtolower($item['reason']), $term);
            });
        }

        // Sort by created_at desc
        $filtered = $filtered->sortByDesc('created_at')->values();

        // Manual pagination
        $perPage = 10;
        $currentPage = $this->getPage();
        $total = $filtered->count();

        $items = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

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
        return view('pages.certification.certification-approval', [
            'headers' => $this->headers(),
            'approvals' => $this->approvals(),
        ]);
    }

    public function openDetailModal(int $id): void
    {
        $approval = collect($this->dummyData)->firstWhere('id', $id);

        if (!$approval) {
            return;
        }

        $this->selectedId = $approval['id'];
        $this->formData = [
            'certification_name' => $approval['certification_name'],
            'competency' => $approval['competency'],
            'reason' => $approval['reason'],
            'status' => $approval['status'],
            'date' => $approval['date'],
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

        // Simulate approve (in real app, update database)
        $this->formData['status'] = 'approved';
        $this->success('Certification request approved', position: 'toast-top toast-center');

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

        // Simulate reject (in real app, update database)
        $this->formData['status'] = 'rejected';
        $this->error('Certification request rejected', position: 'toast-top toast-center');

        $this->modal = false;
    }
}
