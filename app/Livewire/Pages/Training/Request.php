<?php

namespace App\Livewire\Pages\Training;

use App\Models\Request as TrainingRequestModel;
use App\Models\User;
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

    public function mount(): void
    {
        $auth = Auth::user();
        $authId = Auth::id();
        if ($auth && strtolower($auth->role ?? '') === 'spv') {
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
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
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
        $user = Auth::user();
        if (!$user || (strtolower($user->role ?? '') !== 'spv')) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only SPV can create training requests.');
            return;
        }
        $this->reset(['formData', 'selectedId']);
        $this->formData = [
            'user_id' => '',
            'user_name' => '',
            'section' => '',
            'competency' => '',
            'reason' => '',
        ];
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
            'formData.competency' => 'required|string|max:255',
            'formData.reason' => 'required|string|max:255',
            'formData.section' => 'nullable|string',
        ];
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
            'usersSearchable' => $this->usersSearchable,
        ]);
    }

    public function openDetailModal(int $id): void
    {
        $request = TrainingRequestModel::query()
            ->leftJoin('users as creator', 'training_requests.created_by', '=', 'creator.id')
            ->leftJoin('users as target', 'training_requests.user_id', '=', 'target.id')
            ->where('training_requests.id', $id)
            ->select([
                'training_requests.*',
                'target.name as user_name',
                'target.section as user_section',
            ])
            ->firstOrFail();

        $this->selectedId = $request->id;
        $this->formData = [
            'user_id' => $request->user_id,
            'user_name' => $request->user_name,
            'section' => $request->user_section,
            'competency' => $request->competency,
            'reason' => $request->reason,
            'status' => $request->status,
        ];
        $this->mode = 'preview';
        $this->modal = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        // Guard again on save
        $user = Auth::user();
        if (!$user || (strtolower($user->role ?? '') !== 'spv')) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only SPV can create training requests.');
            return;
        }
        $this->validate();

        $creatorId = Auth::id();
        TrainingRequestModel::create([
            'created_by' => $creatorId,
            'user_id' => (int)$this->formData['user_id'],
            'competency' => $this->formData['competency'],
            'reason' => $this->formData['reason'],
            'status' => 'pending',
        ]);

        $this->modal = false;
        $this->dispatch('toast', type: 'success', title: 'Training request created');
    }
}
