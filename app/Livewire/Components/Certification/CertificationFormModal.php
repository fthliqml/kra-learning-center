<?php

namespace App\Livewire\Components\Certification;

use App\Models\Certification;
use App\Models\CertificationModule;
use App\Models\CertificationParticipant;
use App\Models\CertificationSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Mary\Traits\Toast;

class CertificationFormModal extends Component
{
    use Toast;

    public bool $showModal = false;
    public string $activeTab = 'config';
    public bool $isEdit = false;
    public ?int $certificationId = null;

    public ?int $module_id = null;
    public string $certification_name = '';
    public bool $userEditedName = false;
    public ?string $autoModuleTitle = null;

    public array $theory = [
        'date' => '',
        'start_time' => '',
        'end_time' => '',
        'location' => '',
    ];
    public array $practical = [
        'date' => '',
        'start_time' => '',
        'end_time' => '',
        'location' => '',
    ];

    public array $participants = [];

    public array $moduleOptions = [];
    public Collection $usersSearchable;

    protected $listeners = [
        'open-certification-form' => 'openModal',
        'open-certification-form-edit' => 'openEdit',
    ];

    public function mount(): void
    {
        $this->usersSearchable = collect([]);
        $this->userSearch();
        $this->loadModuleOptions();
    }

    private function loadModuleOptions(): void
    {
        $this->moduleOptions = CertificationModule::active()
            ->select('id', 'module_title')
            ->orderBy('module_title')
            ->get()
            ->map(fn($m) => ['id' => $m->id, 'title' => $m->module_title])
            ->toArray();
    }

    public function updatedModuleId($value): void
    {
        $this->autoModuleTitle = null;
        if ($value) {
            $this->autoModuleTitle = collect($this->moduleOptions)->firstWhere('id', (int) $value)['title'] ?? null;
        }
        if (!$this->userEditedName) {
            $this->certification_name = $this->autoModuleTitle ?? '';
        }
        if (!$value) {
            $this->certification_name = '';
            $this->autoModuleTitle = null;
            $this->userEditedName = false;
        }
    }

    public function updatedCertificationName($value): void
    {
        if ($value !== '' && $value !== ($this->autoModuleTitle ?? '')) {
            $this->userEditedName = true;
        } elseif ($value === '' && $this->autoModuleTitle) {
            $this->userEditedName = false;
        }
    }

    public function syncNameFromModule(): void
    {
        if ($this->module_id) {
            $this->autoModuleTitle = collect($this->moduleOptions)->firstWhere('id', (int) $this->module_id)['title'] ?? null;
            if (!$this->userEditedName) {
                $this->certification_name = $this->autoModuleTitle ?? '';
            }
        }
    }

    public function userSearch(string $value = ''): void
    {
        $selectedOptions = collect([]);
        if (!empty($this->participants) && $this->participants !== ['']) {
            $selectedOptions = User::whereIn('id', $this->participants)->get();
        }
        $searchResults = User::when($value, function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%");
        })->limit(10)->get();
        $this->usersSearchable = $searchResults->merge($selectedOptions)
            ->unique('id')
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit($payload): void
    {
        $this->resetForm();
        $this->isEdit = true;
        $id = is_array($payload) ? ($payload['certification_id'] ?? $payload['id'] ?? null) : $payload;
        $this->certificationId = $id ? (int) $id : null;
        $cert = Certification::with(['certificationModule', 'sessions', 'participants'])->find($this->certificationId);
        if (!$cert) {
            $this->error('Certification not found');
            $this->isEdit = false;
            return;
        }
        $this->module_id = $cert->module_id;
        $this->autoModuleTitle = $cert->certificationModule?->module_title;
        $this->certification_name = $cert->name ?: ($this->autoModuleTitle ?? '');
        $theory = $cert->sessions->firstWhere('type', 'THEORY');
        $practical = $cert->sessions->firstWhere('type', 'PRACTICAL');
        if ($theory) {
            $this->theory = [
                'date' => $theory->date instanceof Carbon ? $theory->date->format('Y-m-d') : ($theory->date ? Carbon::parse($theory->date)->format('Y-m-d') : ''),
                'start_time' => $theory->start_time ? Carbon::parse($theory->start_time)->format('H:i') : '',
                'end_time' => $theory->end_time ? Carbon::parse($theory->end_time)->format('H:i') : '',
                'location' => $theory->location ?: '',
            ];
        }
        if ($practical) {
            $this->practical = [
                'date' => $practical->date instanceof Carbon ? $practical->date->format('Y-m-d') : ($practical->date ? Carbon::parse($practical->date)->format('Y-m-d') : ''),
                'start_time' => $practical->start_time ? Carbon::parse($practical->start_time)->format('H:i') : '',
                'end_time' => $practical->end_time ? Carbon::parse($practical->end_time)->format('H:i') : '',
                'location' => $practical->location ?: '',
            ];
        }
        $this->participants = $cert->participants->pluck('employee_id')->map(fn($v) => (int) $v)->toArray();
        $this->userSearch();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->activeTab = 'config';
        $this->isEdit = false;
        $this->certificationId = null;
        $this->module_id = null;
        $this->certification_name = '';
        $this->userEditedName = false;
        $this->autoModuleTitle = null;
        $this->theory = [
            'date' => '',
            'start_time' => '',
            'end_time' => '',
            'location' => '',
        ];
        $this->practical = [
            'date' => '',
            'start_time' => '',
            'end_time' => '',
            'location' => '',
        ];
        $this->participants = [];
        $this->usersSearchable = collect([]);
        $this->userSearch();
    }

    private function validateForm(): void
    {
        $data = [
            'module_id' => $this->module_id,
            'certification_name' => $this->certification_name,
            'theory' => $this->theory,
            'practical' => $this->practical,
            'participants' => $this->participants,
        ];
        $rules = [
            'module_id' => 'required|integer|exists:certification_modules,id',
            'certification_name' => 'nullable|string|max:255',
            'theory.date' => 'required|date',
            'theory.start_time' => 'nullable|date_format:H:i',
            'theory.end_time' => 'nullable|date_format:H:i',
            'theory.location' => 'nullable|string|max:255',
            'practical.date' => 'required|date',
            'practical.start_time' => 'nullable|date_format:H:i',
            'practical.end_time' => 'nullable|date_format:H:i',
            'practical.location' => 'nullable|string|max:255',
            'participants' => 'array',
            'participants.*' => 'integer|exists:users,id',
        ];
        $messages = [
            'required' => ':attribute is required.',
            'date' => ':attribute must be a valid date.',
            'date_format' => ':attribute must be in the format HH:mm.',
            'integer' => ':attribute must be a valid selection.',
            'exists' => 'Selected :attribute does not exist.',
            'array' => ':attribute must be a list.',
            'string' => ':attribute must be text.',
            'max' => ':attribute may not be greater than :max characters.',
        ];
        $attributes = [
            'module_id' => 'Certification module',
            'certification_name' => 'Certification name',
            'theory.date' => 'Theory date',
            'theory.start_time' => 'Theory start time',
            'theory.end_time' => 'Theory end time',
            'theory.location' => 'Theory location',
            'practical.date' => 'Practical date',
            'practical.start_time' => 'Practical start time',
            'practical.end_time' => 'Practical end time',
            'practical.location' => 'Practical location',
            'participants' => 'Participants',
            'participants.*' => 'participant',
        ];
        $validator = Validator::make($data, $rules, $messages, $attributes);
        $validator->after(function ($v) use ($data) {
            foreach (['theory', 'practical'] as $key) {
                $st = $data[$key]['start_time'] ?? null;
                $et = $data[$key]['end_time'] ?? null;
                if ($st && $et) {
                    $start = Carbon::createFromFormat('H:i', $st);
                    $end = Carbon::createFromFormat('H:i', $et);
                    if ($end->lessThanOrEqualTo($start)) {
                        $v->errors()->add("{$key}.end_time", ucfirst($key) . ' end time must be later than start time.');
                    }
                }
            }
        });
        if ($validator->fails()) {
            $all = collect($validator->errors()->all());
            if ($all->isNotEmpty()) {
                $lines = $all->take(8)->map(fn($m) => '• ' . $m)->implode('<br>');
                if ($all->count() > 8) {
                    $lines .= '<br>• (' . ($all->count() - 8) . ' more ...)';
                }
                $this->error($lines, position: 'toast-top toast-center');
            }
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    public function save(): void
    {
        $this->validateForm();
        if ($this->isEdit && $this->certificationId) {
            $this->updateCertification();
            return;
        }
        $this->createCertification();
    }

    private function createCertification(): void
    {
        $moduleId = (int) $this->module_id;
        $name = $this->certification_name ?: (collect($this->moduleOptions)->firstWhere('id', $moduleId)['title'] ?? 'Certification');
        $cert = Certification::create([
            'module_id' => $moduleId,
            'name' => $name,
            'status' => 'scheduled',
        ]);
        $this->persistSessions($cert->id);
        $this->syncParticipants($cert->id);
        $this->success('Certification schedule created successfully!', position: 'toast-top toast-center');
        $this->dispatch('certification-created');
        $this->closeModal();
        $this->resetForm();
    }

    private function updateCertification(): void
    {
        $cert = Certification::with(['sessions', 'participants', 'certificationModule'])->find($this->certificationId);
        if (!$cert) {
            $this->error('Certification not found');
            return;
        }
        $cert->module_id = (int) $this->module_id;
        $cert->name = $this->certification_name ?: ($cert->certificationModule?->module_title ?? $cert->name);
        $cert->save();
        $this->updateSessions($cert);
        $this->updateParticipants($cert);
        $this->success('Certification updated successfully!', position: 'toast-top toast-center');
        $this->dispatch('certification-updated', id: $cert->id);
        $this->closeModal();
        $this->resetForm();
    }

    private function persistSessions(int $certId): void
    {
        $theoryArr = $this->theory;
        CertificationSession::create([
            'certification_id' => $certId,
            'type' => 'THEORY',
            'date' => $theoryArr['date'] ?: null,
            'start_time' => $theoryArr['start_time'] ?: null,
            'end_time' => $theoryArr['end_time'] ?: null,
            'location' => $theoryArr['location'] ?: null,
        ]);
        $practicalArr = $this->practical;
        CertificationSession::create([
            'certification_id' => $certId,
            'type' => 'PRACTICAL',
            'date' => $practicalArr['date'] ?: null,
            'start_time' => $practicalArr['start_time'] ?: null,
            'end_time' => $practicalArr['end_time'] ?: null,
            'location' => $practicalArr['location'] ?: null,
        ]);
    }

    private function updateSessions(Certification $cert): void
    {
        $sessions = $cert->sessions;
        $theory = $sessions->firstWhere('type', 'THEORY');
        $practical = $sessions->firstWhere('type', 'PRACTICAL');
        $t = $this->theory;
        if ($theory) {
            $theory->date = ($t['date'] ?? '') !== '' ? $t['date'] : $theory->date;
            if (($t['start_time'] ?? '') !== '') {
                $theory->start_time = $t['start_time'];
            }
            if (($t['end_time'] ?? '') !== '') {
                $theory->end_time = $t['end_time'];
            }
            if (($t['location'] ?? '') !== '') {
                $theory->location = $t['location'];
            }
            $theory->save();
        }
        $p = $this->practical;
        if ($practical) {
            $practical->date = ($p['date'] ?? '') !== '' ? $p['date'] : $practical->date;
            if (($p['start_time'] ?? '') !== '') {
                $practical->start_time = $p['start_time'];
            }
            if (($p['end_time'] ?? '') !== '') {
                $practical->end_time = $p['end_time'];
            }
            if (($p['location'] ?? '') !== '') {
                $practical->location = $p['location'];
            }
            $practical->save();
        }
    }

    private function syncParticipants(int $certId): void
    {
        $now = Carbon::now();
        foreach ($this->participants as $pid) {
            CertificationParticipant::create([
                'certification_id' => $certId,
                'employee_id' => (int) $pid,
                'assigned_at' => $now,
            ]);
        }
    }

    private function updateParticipants(Certification $cert): void
    {
        $existing = $cert->participants->pluck('employee_id')->map(fn($v) => (int) $v)->toArray();
        $new = array_map('intval', $this->participants);
        $toAdd = array_diff($new, $existing);
        $toRemove = array_diff($existing, $new);
        $now = Carbon::now();
        foreach ($toAdd as $pid) {
            CertificationParticipant::create([
                'certification_id' => $cert->id,
                'employee_id' => $pid,
                'assigned_at' => $now,
            ]);
        }
        if ($toRemove) {
            CertificationParticipant::where('certification_id', $cert->id)->whereIn('employee_id', $toRemove)->delete();
        }
    }

    public function render()
    {
        return view('components.certification.certification-form-modal');
    }
}
