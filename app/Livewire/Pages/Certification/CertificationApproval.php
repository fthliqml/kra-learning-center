<?php

namespace App\Livewire\Pages\Certification;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class CertificationApproval extends Component
{
    use WithPagination;

    public $modal = false;
    public $selectedId = null;

    public $search = '';
    public $filter = 'All';

    public array $formData = [
        'user_id' => '',
        'user_name' => '',
        'section' => '',
        'competency' => '',
        'reason' => '',
    ];

    public $groupOptions = [
        ['value' => 'Pending', 'label' => 'Pending'],
        ['value' => 'Approved', 'label' => 'Approved'],
        ['value' => 'Rejected', 'label' => 'Rejected'],
    ];

    /** Simple flash alert payload: ['type' => 'success|error|warning', 'message' => string] */
    public ?array $flash = null;

    // Dummy data untuk simulasi
    protected $dummyData = [
        ['id' => 1, 'user_id' => 1, 'user_name' => 'Andi Wijaya', 'section' => 'Sub-Assy 3 and 4', 'competency' => 'Welding Advanced', 'reason' => 'Need for new project', 'status' => 'pending', 'date' => '2024-11-01', 'created_at' => '2024-11-01'],
        ['id' => 2, 'user_id' => 2, 'user_name' => 'Budi Santoso', 'section' => 'Main Disassy Power Train', 'competency' => 'Quality Control', 'reason' => 'Skill improvement', 'status' => 'approved', 'date' => '2024-11-02', 'created_at' => '2024-11-02'],
        ['id' => 3, 'user_id' => 3, 'user_name' => 'Citra Dewi', 'section' => 'Sub-Assy 3 and 4', 'competency' => 'Safety Training', 'reason' => 'Mandatory certification', 'status' => 'pending', 'date' => '2024-11-03', 'created_at' => '2024-11-03'],
        ['id' => 4, 'user_id' => 4, 'user_name' => 'Dani Pratama', 'section' => 'Final Assy and Inspection', 'competency' => 'Machine Operation', 'reason' => 'Job rotation', 'status' => 'rejected', 'date' => '2024-11-04', 'created_at' => '2024-11-04'],
        ['id' => 5, 'user_id' => 5, 'user_name' => 'Eka Putri', 'section' => 'Main Disassy Power Train', 'competency' => 'Lean Manufacturing', 'reason' => 'Process improvement', 'status' => 'pending', 'date' => '2024-11-05', 'created_at' => '2024-11-05'],
        ['id' => 6, 'user_id' => 6, 'user_name' => 'Fajar Ramadan', 'section' => 'Sub-Assy 1 and 2', 'competency' => 'Forklift Operation', 'reason' => 'License renewal', 'status' => 'approved', 'date' => '2024-11-06', 'created_at' => '2024-11-06'],
        ['id' => 7, 'user_id' => 7, 'user_name' => 'Gita Sari', 'section' => 'Sub-Assy 3 and 4', 'competency' => 'ISO 9001 Auditor', 'reason' => 'Career development', 'status' => 'pending', 'date' => '2024-11-07', 'created_at' => '2024-11-07'],
        ['id' => 8, 'user_id' => 8, 'user_name' => 'Hendra Kusuma', 'section' => 'Main Disassy Power Train', 'competency' => 'Electrical Systems', 'reason' => 'Technical requirement', 'status' => 'approved', 'date' => '2024-11-08', 'created_at' => '2024-11-08'],
        ['id' => 9, 'user_id' => 9, 'user_name' => 'Indah Permata', 'section' => 'Final Assy and Inspection', 'competency' => 'Project Management', 'reason' => 'Promotion preparation', 'status' => 'pending', 'date' => '2024-11-09', 'created_at' => '2024-11-09'],
        ['id' => 10, 'user_id' => 10, 'user_name' => 'Joko Widodo', 'section' => 'Sub-Assy 1 and 2', 'competency' => 'Six Sigma Green Belt', 'reason' => 'Quality initiative', 'status' => 'rejected', 'date' => '2024-11-10', 'created_at' => '2024-11-10'],
        ['id' => 11, 'user_id' => 11, 'user_name' => 'Kartika Sari', 'section' => 'Main Disassy Power Train', 'competency' => 'AutoCAD Professional', 'reason' => 'Design work', 'status' => 'pending', 'date' => '2024-11-11', 'created_at' => '2024-11-11'],
        ['id' => 12, 'user_id' => 12, 'user_name' => 'Lukman Hakim', 'section' => 'Sub-Assy 3 and 4', 'competency' => 'Hydraulic Systems', 'reason' => 'Maintenance team', 'status' => 'approved', 'date' => '2024-11-12', 'created_at' => '2024-11-12'],
        ['id' => 13, 'user_id' => 13, 'user_name' => 'Maya Anggraini', 'section' => 'Final Assy and Inspection', 'competency' => 'Statistical Process Control', 'reason' => 'Data analysis', 'status' => 'pending', 'date' => '2024-11-13', 'created_at' => '2024-11-13'],
        ['id' => 14, 'user_id' => 14, 'user_name' => 'Nugroho Adi', 'section' => 'Sub-Assy 1 and 2', 'competency' => 'Leadership Development', 'reason' => 'Team leader candidate', 'status' => 'approved', 'date' => '2024-11-14', 'created_at' => '2024-11-14'],
        ['id' => 15, 'user_id' => 15, 'user_name' => 'Olivia Tan', 'section' => 'Main Disassy Power Train', 'competency' => 'CNC Programming', 'reason' => 'New machine introduction', 'status' => 'pending', 'date' => '2024-11-15', 'created_at' => '2024-11-15'],
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
            ['key' => 'user', 'label' => 'Name', 'class' => '!text-center !md:w-[27%]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center !md:w-[21%]'],
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
                return str_contains(strtolower($item['user_name']), $term) ||
                    str_contains(strtolower($item['section']), $term) ||
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
            'user_id' => $approval['user_id'],
            'user_name' => $approval['user_name'],
            'section' => $approval['section'],
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
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only LID leader can approve.');
            return;
        }

        // Simulate approve (in real app, update database)
        $this->formData['status'] = 'approved';
        $this->flash = ['type' => 'success', 'message' => 'Certification request approved'];
    }

    /** Reject selected request */
    public function reject(): void
    {
        if (!$this->selectedId) {
            return;
        }
        if (!$this->canModerate()) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only LID leader can reject.');
            return;
        }

        // Simulate reject (in real app, update database)
        $this->formData['status'] = 'rejected';
        $this->flash = ['type' => 'error', 'message' => 'Certification request rejected'];
    }
}
