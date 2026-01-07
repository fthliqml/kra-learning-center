<?php

namespace App\Livewire\Pages\Training;

use App\Exports\TrainingRequestExport;
use App\Models\Competency;
use App\Models\Request as TrainingRequestModel;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Request extends Component
{
    use WithPagination;

    public $modal = false;
    public $mode = 'create';
    public $selectedId = null;

    public $search = '';
    public $filter = 'All';

    /** Searchable user options for x-choices */
    public array $usersSearchable = [];
    /** Cached users list (id, name, section) */
    public array $users = [];

    /** Competency options for x-choices */
    public array $competencies = [];

    /** Group Comp options for dropdown */
    public array $groupCompOptions = [];

    /** Selected group comp for filtering */
    public string $selectedGroupComp = '';

    public array $formData = [
        'user_id' => '',
        'user_name' => '',
        'section' => '',
        'competency_id' => '',
        'group_comp' => '',
        'reason' => '',
    ];

    public $groupOptions = [
        ['value' => 'Pending', 'label' => 'Pending'],
        ['value' => 'Approved', 'label' => 'Approved'],
        ['value' => 'Rejected', 'label' => 'Rejected'],
    ];

    /** Simple flash alert payload: ['type' => 'success|error|warning', 'message' => string] */
    public ?array $flash = null;

    public function mount(): void
    {
        /** @var User|null $auth */
        $auth = Auth::user();
        $authId = Auth::id();
        if ($auth && $auth->hasPosition('supervisor')) {
            $section = $auth->section;
            $this->users = User::query()
                ->select('id', 'name', 'section')
                ->when($section, fn($q) => $q->where('section', $section))
                ->when($authId, fn($q) => $q->where('id', '!=', $authId))
                ->orderBy('name')
                ->get()
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'section' => $u->section])
                ->all();
        } else {
            // Non-SPV cannot create; keep list empty for safety
            $this->users = [];
        }

        // initial suggestion list (top 15) from filtered users
        $this->usersSearchable = collect($this->users)
            ->take(15)
            ->map(fn($u) => ['id' => $u['id'], 'name' => $u['name']])
            ->values()
            ->all();

        // Load unique group comp types for dropdown
        $this->groupCompOptions = Competency::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->get()
            ->map(fn($c) => ['value' => $c->type, 'label' => $c->type])
            ->all();

        // Initially load all competencies (will be filtered when group comp is selected)
        $this->loadCompetencies();
    }

    protected function loadCompetencies(): void
    {
        // Only load competencies if group comp is selected
        if (!$this->selectedGroupComp) {
            $this->competencies = [];
            return;
        }

        $this->competencies = Competency::query()
            ->where('type', $this->selectedGroupComp)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    public function updated($property): void
    {
        // Only reset page for search/filter, not for modal or form data
        if (in_array($property, ['search', 'filter'])) {
            $this->resetPage();
        }

        // When group comp changes, reload competencies and reset selection
        if ($property === 'selectedGroupComp') {
            $this->loadCompetencies();
            $this->formData['competency_id'] = '';
            $this->formData['group_comp'] = $this->selectedGroupComp;
        }
    }

    /** Live search for users used by x-choices */
    public function userSearch(string $q = ''): void
    {
        $q = trim($q);
        // Always search within already filtered (same section) users
        $source = collect($this->users);
        if ($q !== '') {
            $source = $source->filter(fn($u) => stripos($u['name'], $q) !== false);
        }
        $this->usersSearchable = $source
            ->take(15)
            ->map(fn($u) => ['id' => $u['id'], 'name' => $u['name']])
            ->values()
            ->all();
    }

    public function openCreateModal(): void
    {
        // Only SPV can create training requests
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->hasPosition('supervisor')) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only SPV can create training requests.');
            return;
        }
        $this->reset(['formData', 'selectedId', 'selectedGroupComp']);
        $this->formData = [
            'user_id' => '',
            'user_name' => '',
            'section' => '',
            'competency_id' => '',
            'group_comp' => '',
            'reason' => '',
        ];
        $this->loadCompetencies(); // Reload all competencies
        $this->mode = 'create';
        $this->modal = true;
        $this->resetValidation();
    }

    public function updatedFormDataUserId($value): void
    {
        // Disallow selecting self
        if (Auth::id() && (int)$value === (int)Auth::id()) {
            $this->formData['user_id'] = '';
            $this->formData['section'] = '';
            $this->dispatch('toast', type: 'error', title: 'Invalid selection', message: 'SPV cannot select themselves.');
            return;
        }

        $user = collect($this->users)->firstWhere('id', (int)$value);
        $this->formData['section'] = $user['section'] ?? '';
    }

    public function updatedFormDataCompetencyId($value): void
    {
        if (!$value) {
            $this->formData['group_comp'] = '';
            return;
        }

        $competency = Competency::find((int)$value);
        $this->formData['group_comp'] = $competency->type ?? '';
    }

    protected function rules(): array
    {
        return [
            'formData.user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attr, $value, $fail) {
                    if (Auth::id() && (int)$value === (int)Auth::id()) {
                        $fail('SPV cannot select themselves.');
                    }
                }
            ],
            'formData.competency_id' => 'required|integer|exists:competency,id',
            'formData.reason' => 'required|string|max:255',
            'formData.section' => 'nullable|string',
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12 md:w-[8%]'],
            ['key' => 'user', 'label' => 'Name', 'class' => 'md:w-[36%]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center md:w-[18%]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center md:w-[20%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center md:w-[10%]'],
        ];
    }

    /**
     * Export current Training Requests view to Excel.
     * Only available for admin and LID Section Head.
     */
    public function export()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');
        $isLidSectionHead = $user->hasPosition('section_head') && strtoupper($user->section ?? '') === 'LID';

        if (!$isAdmin && !$isLidSectionHead) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only admin or LID Section Head can export training requests.');
            return null;
        }

        $statusFilter = strtolower($this->filter ?? 'all');
        $search = trim($this->search ?? '');

        return Excel::download(
            new TrainingRequestExport($user, $statusFilter, $search),
            'training_requests_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function requests()
    {
        /** @var User|null $auth */
        $auth = Auth::user();

        $query = TrainingRequestModel::query()
            ->leftJoin('users', 'training_requests.created_by', '=', 'users.id')
            ->leftJoin('users as u2', 'training_requests.user_id', '=', 'u2.id')
            ->when($this->filter && strtolower($this->filter) !== 'all', function ($q) {
                $q->where('training_requests.status', strtolower($this->filter));
            })
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('competency.name', 'like', $term)
                        ->orWhere('training_requests.reason', 'like', $term)
                        ->orWhere('users.name', 'like', $term)
                        ->orWhere('u2.name', 'like', $term)
                        ->orWhere('u2.section', 'like', $term);
                });
            })
            ->when($auth, function ($q) use ($auth) {
                $section = strtolower(trim($auth->section ?? ''));
                $department = strtolower(trim($auth->department ?? ''));
                $division = strtolower(trim($auth->division ?? ''));

                // User dengan section LID (kecuali Division Head LID) bisa melihat semua data lintas area
                // tanpa pembatasan status tambahan; status tetap mengikuti dropdown filter di atas.
                if ($section === 'lid' && !$auth->hasPosition('division_head')) {
                    // Jangan tambah filter area ataupun status tambahan, langsung return
                    return;
                }

                if ($auth->hasPosition('supervisor') && $section !== '') {
                    // Target user (u2) section sama dengan section SPV
                    $q->whereRaw('LOWER(u2.section) = ?', [$section]);
                } elseif ($auth->hasPosition('department_head') && $department !== '') {
                    // Target user (u2) department sama dengan department Dept Head
                    $q->whereRaw('LOWER(u2.department) = ?', [$department]);
                } elseif ($auth->hasPosition('division_head')) {
                    // Khusus Division Head LID, bisa melihat semua request yang sudah di tahap final (lid_division_head)
                    if ($division === 'human capital, finance & general support') {
                        $q->where('training_requests.approval_stage', \App\Models\Request::STAGE_LID_DIV_HEAD);
                    } elseif ($division !== '') {
                        // Division Head lain hanya melihat request untuk division mereka sendiri
                        $q->whereRaw('LOWER(u2.division) = ?', [$division]);
                    }
                }
            })
            ->leftJoin('competency', 'training_requests.competency_id', '=', 'competency.id')
            ->orderBy('training_requests.created_at', 'desc')
            ->select(
                'training_requests.*',
                'users.name as created_by_name',
                'u2.name as user_name',
                'u2.section as section',
                'u2.department as department',
                'u2.division as division',
                'competency.name as competency_name'
            );

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
            'usersSearchable' => $this->usersSearchable,
            'competencies' => $this->competencies,
        ]);
    }

    public function openDetailModal(int $id): void
    {
        $request = TrainingRequestModel::query()
            ->leftJoin('users as creator', 'training_requests.created_by', '=', 'creator.id')
            ->leftJoin('users as target', 'training_requests.user_id', '=', 'target.id')
            ->leftJoin('competency', 'training_requests.competency_id', '=', 'competency.id')
            ->where('training_requests.id', $id)
            ->select([
                'training_requests.*',
                'target.name as user_name',
                'target.section as user_section',
                'target.department as user_department',
                'target.division as user_division',
                'competency.name as competency_name',
                'competency.type as group_comp',
            ])
            ->firstOrFail();

        $this->selectedId = $request->id;
        $this->formData = [
            'user_id' => $request->user_id,
            'user_name' => $request->user_name,
            'section' => $request->user_section,
            'department' => $request->user_department,
            'division' => $request->user_division,
            'competency_id' => $request->competency_id,
            'competency_name' => $request->competency_name ?? '',
            'group_comp' => $request->group_comp ?? '',
            'reason' => $request->reason,
            'status' => $request->status,
            'approval_stage' => $request->approval_stage,
        ];
        $this->mode = 'preview';
        $this->modal = true;
        $this->resetValidation();
    }

    /**
     * Determine if the current authenticated user can moderate (approve/reject)
     * a training request at its current approval stage.
     *
     * Flow yang diinginkan:
     *  - SPV mengajukan (creator)
     *  - 1st approver  : Dept Head area terkait (section sama dengan user target)
     *  - 2nd approver  : Division Head area terkait (section sama dengan user target)
     *  - Final approver: Division Head LID
     */
    protected function canModerate(TrainingRequestModel $request): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $targetDept = strtolower(trim($request->user?->department ?? ''));
        $targetDiv = strtolower(trim($request->user?->division ?? ''));
        $userDept = strtolower(trim($user->department ?? ''));
        $userDiv = strtolower(trim($user->division ?? ''));

        // Dept Head area terkait (berdasarkan DEPARTMENT yang sama)
        if ($request->approval_stage === \App\Models\Request::STAGE_DEPT_HEAD) {
            return $user->hasPosition('department_head') && $targetDept !== '' && $userDept === $targetDept;
        }

        // Division Head area terkait (berdasarkan DIVISION yang sama)
        if ($request->approval_stage === \App\Models\Request::STAGE_AREA_DIV_HEAD) {
            return $user->hasPosition('division_head') && $targetDiv !== '' && $userDiv === $targetDiv;
        }

        // Division Head LID (final) - gunakan Division LID (Human Capital, Finance & General Support)
        if ($request->approval_stage === \App\Models\Request::STAGE_LID_DIV_HEAD) {
            return $user->hasPosition('division_head') && $userDiv === 'human capital, finance & general support';
        }

        return false;
    }

    /** Approve selected request */
    public function approve(): void
    {
        if (!$this->selectedId) {
            return; // nothing selected
        }
        $req = TrainingRequestModel::with('user')->find($this->selectedId);
        if (!$req) {
            $this->dispatch('toast', type: 'error', title: 'Not found', message: 'Request no longer exists.');
            return;
        }
        if (!$this->canModerate($req)) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'You are not allowed to approve this request at its current stage.');
            return;
        }
        if (strtolower($req->status) !== 'pending') {
            $this->dispatch('toast', type: 'warning', title: 'Already processed', message: 'Request already resolved.');
            return;
        }
        // Advance approval stage or finalize approval
        if ($req->approval_stage === \App\Models\Request::STAGE_DEPT_HEAD) {
            $req->approval_stage = \App\Models\Request::STAGE_AREA_DIV_HEAD;
            $req->save();
            $this->formData['approval_stage'] = $req->approval_stage;
            $this->flash = ['type' => 'success', 'message' => 'Approved by Dept Head. Waiting for Division Head area approval.'];
        } elseif ($req->approval_stage === \App\Models\Request::STAGE_AREA_DIV_HEAD) {
            $req->approval_stage = \App\Models\Request::STAGE_LID_DIV_HEAD;
            $req->save();
            $this->formData['approval_stage'] = $req->approval_stage;
            $this->flash = ['type' => 'success', 'message' => 'Approved by Division Head area. Waiting for Division Head LID approval.'];
        } else { // LID Division Head stage -> final approval
            $req->status = 'approved';
            $req->save();
            $this->formData['status'] = 'approved';
            $this->formData['approval_stage'] = $req->approval_stage;
            $this->flash = ['type' => 'success', 'message' => 'Training request fully approved'];
        }
    }

    /** Reject selected request */
    public function reject(): void
    {
        if (!$this->selectedId) {
            return; // nothing selected
        }
        $req = TrainingRequestModel::with('user')->find($this->selectedId);
        if (!$req) {
            $this->dispatch('toast', type: 'error', title: 'Not found', message: 'Request no longer exists.');
            return;
        }
        if (!$this->canModerate($req)) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'You are not allowed to reject this request at its current stage.');
            return;
        }
        if (strtolower($req->status) !== 'pending') {
            $this->dispatch('toast', type: 'warning', title: 'Already processed', message: 'Request already resolved.');
            return;
        }
        $req->update(['status' => 'rejected']);
        $this->formData['status'] = 'rejected';
        // Rejection should trigger a red alert
        $this->flash = ['type' => 'error', 'message' => 'Training request rejected'];
    }

    public function save(): void
    {
        // Guard again on save
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->hasPosition('supervisor')) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only SPV can create training requests.');
            return;
        }
        $this->validate();

        $creatorId = Auth::id();
        TrainingRequestModel::create([
            'created_by' => $creatorId,
            'user_id' => (int)$this->formData['user_id'],
            'competency_id' => (int)$this->formData['competency_id'],
            'reason' => $this->formData['reason'],
            'status' => 'pending',
            // Mulai dari tahap Dept Head area terkait
            'approval_stage' => \App\Models\Request::STAGE_DEPT_HEAD,
        ]);

        $this->modal = false;
        $this->flash = ['type' => 'success', 'message' => 'Training request created'];
    }
}
