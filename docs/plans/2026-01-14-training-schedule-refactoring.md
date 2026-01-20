# Training Schedule Refactoring Implementation Plan

> **REQUIRED SUB-WORKFLOW:** Use `/execute-plan` workflow to implement this plan task-by-task.

**Goal:** Refactor Training Schedule for instant modal response, client-side filtering, and maintainable component structure.

**Architecture:** Hybrid Alpine+Livewire pattern for instant UI feedback. Split mega-component into focused child components communicating via events and shared traits.

**Tech Stack:** Laravel 12, Livewire 3.6, Alpine.js, Mary UI, Blade

---

## Task 1: Create TrainingFormState Trait

**Files:**
- Create: `app/Livewire/Components/Training/TrainingForm/TrainingFormState.php`

#### Step 1: Create shared state trait

```php
<?php

namespace App\Livewire\Components\Training\TrainingForm;

use App\Models\Competency;
use App\Models\Course;
use App\Models\TrainingModule;

trait TrainingFormState
{
    // ===== MODE & IDENTITY =====
    public bool $isEdit = false;
    public ?int $trainingId = null;
    
    // ===== FORM FIELDS =====
    public string $training_name = '';
    public string $training_type = 'IN';
    public string $group_comp = 'BMC';
    public ?int $selected_module_id = null;
    public ?int $course_id = null;
    public ?int $competency_id = null;
    public string $date = '';
    public string $start_time = '';
    public string $end_time = '';
    public ?int $trainerId = null;
    public array $room = ['name' => '', 'location' => ''];
    public array $participants = [];
    
    // ===== DROPDOWN OPTIONS =====
    public array $courseOptions = [];
    public array $trainingModuleOptions = [];
    public array $competencyOptions = [];
    
    // ===== ORIGINAL TYPE (for edit mode) =====
    public ?string $originalTrainingType = null;
    
    /**
     * Load all dropdown options (lazy loaded)
     */
    public function loadDropdowns(): void
    {
        $this->courseOptions = Course::with('competency:id,type')
            ->select('id', 'title', 'competency_id')
            ->orderBy('title')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'group_comp' => $c->competency->type ?? null])
            ->toArray();
            
        $this->trainingModuleOptions = TrainingModule::with('competency')
            ->orderBy('title')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'group_comp' => $m->competency?->type ?? null
            ])->toArray();
            
        $this->competencyOptions = Competency::query()
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => trim($c->code . ' - ' . $c->name)])
            ->toArray();
    }
    
    /**
     * Reset all form fields
     */
    public function resetFormState(): void
    {
        $this->isEdit = false;
        $this->trainingId = null;
        $this->training_name = '';
        $this->training_type = 'IN';
        $this->group_comp = 'BMC';
        $this->selected_module_id = null;
        $this->course_id = null;
        $this->competency_id = null;
        $this->date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->trainerId = null;
        $this->room = ['name' => '', 'location' => ''];
        $this->participants = [];
        $this->originalTrainingType = null;
    }
    
    /**
     * Get form data as array for saving
     */
    public function getFormData(): array
    {
        return [
            'training_name' => $this->training_name,
            'training_type' => $this->training_type,
            'group_comp' => $this->group_comp,
            'selected_module_id' => $this->selected_module_id,
            'course_id' => $this->course_id,
            'competency_id' => $this->competency_id,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'trainerId' => $this->trainerId,
            'room' => $this->room,
            'participants' => $this->participants,
        ];
    }
}
```

#### Step 2: Verify file syntax

Run: `php -l app/Livewire/Components/Training/TrainingForm/TrainingFormState.php`
Expected: No syntax errors

#### Step 3: Commit

```bash
git add app/Livewire/Components/Training/TrainingForm/TrainingFormState.php
git commit -m "feat(training): add TrainingFormState trait for shared form state"
```

---

## Task 2: Create New TrainingFormModal (Parent)

**Files:**
- Create: `app/Livewire/Components/Training/TrainingForm/TrainingFormModal.php`
- Create: `resources/views/livewire/training/training-form-modal.blade.php`

#### Step 1: Create parent modal component

```php
<?php

namespace App\Livewire\Components\Training\TrainingForm;

use App\Models\Training;
use App\Models\Trainer;
use App\Models\User;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Collection;

class TrainingFormModal extends Component
{
    use Toast, TrainingFormState;
    
    // ===== MODAL STATE =====
    public bool $showModal = false;
    public bool $loading = true;
    public string $activeTab = 'training';
    
    // ===== SEARCHABLE COLLECTIONS =====
    public Collection $usersSearchable;
    public Collection $trainersSearchable;
    
    protected $listeners = [
        'open-training-form' => 'openModal',
        'open-training-form-edit' => 'openEditModal',
        'confirm-delete-training-form' => 'onConfirmDelete',
    ];
    
    public function mount(): void
    {
        $this->usersSearchable = collect([]);
        $this->trainersSearchable = collect([]);
    }
    
    /**
     * Load form data - called via Alpine after modal opens
     */
    public function loadFormData(?array $payload = null): void
    {
        $this->loadDropdowns();
        $this->loadSearchables();
        
        if (!empty($payload['id'])) {
            $this->fillFromTraining((int) $payload['id']);
        }
        
        $this->loading = false;
    }
    
    /**
     * Open modal for new training
     */
    public function openModal(?array $data = null): void
    {
        $this->resetFormState();
        $this->loading = true;
        $this->activeTab = 'training';
        
        if (!empty($data['date'])) {
            $this->date = $data['date'];
        }
        
        $this->showModal = true;
        
        // Data will be loaded via Alpine calling loadFormData()
    }
    
    /**
     * Open modal for editing existing training
     */
    public function openEditModal($payload): void
    {
        $id = is_array($payload) ? ($payload['id'] ?? null) : $payload;
        
        if (!$id) {
            $this->error('Invalid training reference.');
            return;
        }
        
        $this->resetFormState();
        $this->loading = true;
        $this->isEdit = true;
        $this->trainingId = (int) $id;
        $this->activeTab = 'training';
        $this->showModal = true;
        
        // Data will be loaded via Alpine calling loadFormData()
    }
    
    /**
     * Close modal and reset
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetFormState();
        $this->resetErrorBag();
    }
    
    /**
     * Save training (create or update)
     */
    public function save(): void
    {
        // Validation and saving logic delegated to child tabs
        // This method orchestrates the flow
        $this->dispatch('save-training-request');
    }
    
    /**
     * Load searchable collections
     */
    private function loadSearchables(): void
    {
        $this->trainersSearchable = Trainer::with('user')
            ->get()
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name ?: ($t->user?->name ?? 'Unknown')]);
            
        $this->usersSearchable = User::limit(20)
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);
    }
    
    /**
     * Fill form from existing training
     */
    private function fillFromTraining(int $id): void
    {
        $training = Training::with(['sessions', 'course', 'module', 'competency'])->find($id);
        
        if (!$training) {
            $this->error('Training not found');
            return;
        }
        
        // Fill form fields from training model
        $this->training_type = $training->type;
        $this->originalTrainingType = $training->type;
        $this->training_name = $training->name;
        $this->competency_id = $training->competency_id;
        $this->course_id = $training->course_id;
        $this->group_comp = $training->group_comp ?? 'BMC';
        
        // Date handling
        if ($training->start_date && $training->end_date) {
            $this->date = $training->start_date->format('Y-m-d') . ' to ' . $training->end_date->format('Y-m-d');
        }
        
        // Session data from first session
        $firstSession = $training->sessions->first();
        if ($firstSession) {
            $this->trainerId = $firstSession->trainer_id;
            $this->room = [
                'name' => $firstSession->room_name ?? '',
                'location' => $firstSession->room_location ?? '',
            ];
            $this->start_time = $firstSession->start_time ? \Carbon\Carbon::parse($firstSession->start_time)->format('H:i') : '';
            $this->end_time = $firstSession->end_time ? \Carbon\Carbon::parse($firstSession->end_time)->format('H:i') : '';
        }
        
        // Set module/course based on type
        if ($training->type === 'IN') {
            $this->selected_module_id = $training->module_id;
        } elseif (in_array($training->type, ['LMS', 'BLENDED'])) {
            $this->selected_module_id = $training->course_id;
        }
        
        // Participants
        $this->participants = $training->assessments->pluck('employee_id')->map(fn($id) => (int) $id)->toArray();
    }
    
    public function render()
    {
        return view('livewire.training.training-form-modal');
    }
}
```

#### Step 2: Create blade template with instant modal pattern

```blade
{{-- resources/views/livewire/training/training-form-modal.blade.php --}}
<div x-data="trainingFormModal()" x-init="init()" @open-training-form.window="openModal($event.detail)">
    
    {{-- Trigger Button --}}
    <x-ui.button @click="openModal()" variant="primary">
        <x-icon name="o-plus" class="w-5 h-5" />
        Add <span class="hidden sm:block">New Training</span>
    </x-ui.button>

    {{-- Modal with instant open via Alpine --}}
    <x-modal x-show="open" @close="closeModal()" :title="$isEdit ? 'Edit Training' : 'New Training'" box-class="backdrop-blur max-w-4xl">
        
        {{-- Loading Skeleton --}}
        <div x-show="loading" class="space-y-4">
            <div class="animate-pulse">
                <div class="h-10 bg-gray-200 rounded mb-4"></div>
                <div class="h-10 bg-gray-200 rounded mb-4"></div>
                <div class="h-10 bg-gray-200 rounded mb-4"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="h-10 bg-gray-200 rounded"></div>
                    <div class="h-10 bg-gray-200 rounded"></div>
                </div>
            </div>
        </div>
        
        {{-- Actual Form Content --}}
        <div x-show="!loading" x-cloak>
            <x-tabs wire:model="activeTab">
                <x-tab name="training" label="Training Config" icon="o-academic-cap">
                    <livewire:components.training.training-form.training-config-tab 
                        :training-type="$training_type"
                        :group-comp="$group_comp"
                        :selected-module-id="$selected_module_id"
                        :course-options="$courseOptions"
                        :training-module-options="$trainingModuleOptions"
                        :competency-options="$competencyOptions"
                        :date="$date"
                        :is-edit="$isEdit"
                    />
                </x-tab>
                
                <x-tab name="session" label="Session Config" icon="o-cog-6-tooth">
                    <livewire:components.training.training-form.session-config-tab
                        :training-type="$training_type"
                        :trainer-id="$trainerId"
                        :room="$room"
                        :start-time="$start_time"
                        :end-time="$end_time"
                        :participants="$participants"
                        :trainers-searchable="$trainersSearchable"
                        :users-searchable="$usersSearchable"
                    />
                </x-tab>
            </x-tabs>
        </div>
        
        {{-- Modal Actions --}}
        <x-slot:actions>
            <div class="flex justify-between items-center w-full">
                <x-button label="Cancel" @click="closeModal()" class="btn-ghost" />
                <x-button :label="$isEdit ? 'Update' : 'Save'" wire:click="save" class="btn-primary" spinner="save" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>

@script
<script>
function trainingFormModal() {
    return {
        open: false,
        loading: true,
        
        init() {
            // Listen for open events
            this.$watch('open', value => {
                if (value && this.loading) {
                    // Modal just opened, load data
                    $wire.loadFormData().then(() => {
                        this.loading = false;
                    });
                }
            });
        },
        
        openModal(payload = null) {
            this.open = true;
            this.loading = true;
            
            if (payload?.id) {
                $wire.openEditModal(payload);
            } else {
                $wire.openModal(payload);
            }
        },
        
        closeModal() {
            this.open = false;
            this.loading = true;
            $wire.closeModal();
        }
    }
}
</script>
@endscript
```

#### Step 3: Verify syntax

Run: `php -l app/Livewire/Components/Training/TrainingForm/TrainingFormModal.php`
Expected: No syntax errors

#### Step 4: Commit

```bash
git add app/Livewire/Components/Training/TrainingForm/TrainingFormModal.php
git add resources/views/livewire/training/training-form-modal.blade.php
git commit -m "feat(training): add new TrainingFormModal with instant open pattern"
```

---

## Task 3: Create TrainingConfigTab Component

**Files:**
- Create: `app/Livewire/Components/Training/TrainingForm/TrainingConfigTab.php`
- Create: `resources/views/livewire/training/training-config-tab.blade.php`

#### Step 1: Create tab component

```php
<?php

namespace App\Livewire\Components\Training\TrainingForm;

use Livewire\Component;
use Livewire\Attributes\Modelable;

class TrainingConfigTab extends Component
{
    // Properties bound to parent via wire:model
    #[Modelable]
    public string $trainingType = 'IN';
    
    #[Modelable]
    public string $groupComp = 'BMC';
    
    #[Modelable]
    public ?int $selectedModuleId = null;
    
    #[Modelable]
    public string $date = '';
    
    #[Modelable]
    public ?int $competencyId = null;
    
    #[Modelable]
    public string $trainingName = '';
    
    // Dropdown options (passed from parent)
    public array $courseOptions = [];
    public array $trainingModuleOptions = [];
    public array $competencyOptions = [];
    public bool $isEdit = false;
    
    public array $trainingTypeOptions = [
        ['id' => 'IN', 'name' => 'In-House'],
        ['id' => 'OUT', 'name' => 'Out-House'],
        ['id' => 'LMS', 'name' => 'LMS'],
        ['id' => 'BLENDED', 'name' => 'Blended'],
    ];
    
    public function updatedTrainingType($value): void
    {
        // Auto-sync group_comp based on selection
        $this->dispatch('training-type-changed', type: $value);
        
        // Reset related fields
        if ($value !== 'OUT') {
            $this->competencyId = null;
        }
        if (!in_array($value, ['LMS', 'BLENDED'])) {
            // Reset to default
        }
    }
    
    public function updatedSelectedModuleId($value): void
    {
        if (!$value) return;
        
        if (in_array($this->trainingType, ['LMS', 'BLENDED'])) {
            // Course selected - sync group_comp
            $course = collect($this->courseOptions)->firstWhere('id', $value);
            if ($course) {
                $this->groupComp = $course['group_comp'] ?? 'BMC';
                $this->trainingName = $course['title'] ?? '';
            }
        } else {
            // Module selected - sync group_comp
            $module = collect($this->trainingModuleOptions)->firstWhere('id', $value);
            if ($module) {
                $this->groupComp = $module['group_comp'] ?? 'BMC';
                $this->trainingName = $module['title'] ?? '';
            }
        }
    }
    
    public function render()
    {
        return view('livewire.training.training-config-tab');
    }
}
```

#### Step 2: Create blade template

```blade
{{-- resources/views/livewire/training/training-config-tab.blade.php --}}
<div class="space-y-4">
    {{-- Training Type --}}
    <x-choices label="Training Type" wire:model.live="trainingType" :options="$trainingTypeOptions"
        option-value="id" option-label="name" icon="o-book-open" single class="focus-within:border-0" />
    
    {{-- Module/Course Selection based on type --}}
    @if ($trainingType === 'LMS' || $trainingType === 'BLENDED')
        <x-choices label="Course" wire:model.live="selectedModuleId" :options="$courseOptions"
            option-value="id" option-label="title" placeholder="Select course"
            icon="o-rectangle-group" single searchable class="focus-within:border-0" />
    @elseif ($trainingType === 'IN')
        <x-choices label="Training Module" wire:model.live="selectedModuleId" :options="$trainingModuleOptions"
            option-value="id" option-label="title" placeholder="Select training module"
            icon="o-academic-cap" single searchable class="focus-within:border-0" />
        <x-input wire:model.live="trainingName" label="Training Name (Optional)"
            placeholder="Edit training name or leave as selected module"
            class="focus-within:border-0" hint="You can customize the training name" />
    @elseif ($trainingType === 'OUT')
        <x-choices label="Competency" wire:model.live="competencyId" :options="$competencyOptions"
            option-value="id" option-label="name" placeholder="Select competency"
            icon="o-academic-cap" single searchable class="focus-within:border-0" />
        <x-input wire:model.live="trainingName" label="Training Name (Optional)"
            placeholder="Auto-filled from selected competency"
            class="focus-within:border-0" hint="Auto-filled from selected competency" />
    @else
        <x-input wire:model="trainingName" label="Training Name" placeholder="Enter training name"
            class="focus-within:border-0" />
    @endif
    
    {{-- Group Competency (readonly) --}}
    <x-input label="Group Competency" wire:model="groupComp" readonly
        icon="o-clipboard-document" class="focus-within:border-0 bg-gray-50"
        hint="Synced from selected module/course" />
    
    {{-- Date Range --}}
    <x-datepicker wire:model.defer="date" placeholder="Select date range" icon="o-calendar"
        class="w-full" label="Training Date" :config="[
            'mode' => 'range',
            'altInput' => true,
            'altFormat' => 'd M Y',
        ]" />
</div>
```

#### Step 3: Verify and commit

Run: `php -l app/Livewire/Components/Training/TrainingForm/TrainingConfigTab.php`
Expected: No syntax errors

```bash
git add app/Livewire/Components/Training/TrainingForm/TrainingConfigTab.php
git add resources/views/livewire/training/training-config-tab.blade.php
git commit -m "feat(training): add TrainingConfigTab component"
```

---

## Task 4: Create SessionConfigTab Component

**Files:**
- Create: `app/Livewire/Components/Training/TrainingForm/SessionConfigTab.php`
- Create: `resources/views/livewire/training/session-config-tab.blade.php`

#### Step 1: Create component

```php
<?php

namespace App\Livewire\Components\Training\TrainingForm;

use App\Models\Trainer;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Modelable;
use Illuminate\Support\Collection;

class SessionConfigTab extends Component
{
    #[Modelable]
    public ?int $trainerId = null;
    
    #[Modelable]
    public array $room = ['name' => '', 'location' => ''];
    
    #[Modelable]
    public string $startTime = '';
    
    #[Modelable]
    public string $endTime = '';
    
    #[Modelable]
    public array $participants = [];
    
    public string $trainingType = 'IN';
    public Collection $trainersSearchable;
    public Collection $usersSearchable;
    
    public function trainerSearch(string $value = ''): void
    {
        $selected = collect([]);
        if (!empty($this->trainerId)) {
            $selected = Trainer::with('user')->where('id', $this->trainerId)->get();
        }

        if (empty($value)) {
            $results = Trainer::with('user')->get();
        } else {
            $results = Trainer::with('user')
                ->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$value}%"));
                })
                ->limit(10)
                ->get();
        }

        $this->trainersSearchable = $results->merge($selected)
            ->unique('id')
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name ?: ($t->user?->name ?? 'Unknown')]);
    }
    
    public function userSearch(string $value = ''): void
    {
        $selectedOptions = collect([]);
        if (!empty($this->participants) && $this->participants !== ['']) {
            $selectedOptions = User::whereIn('id', $this->participants)->get();
        }

        $searchResults = User::where('name', 'like', "%{$value}%")
            ->limit(10)
            ->get();

        $this->usersSearchable = $searchResults->merge($selectedOptions)
            ->unique('id')
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);
    }
    
    public function render()
    {
        return view('livewire.training.session-config-tab');
    }
}
```

#### Step 2: Create blade template

```blade
{{-- resources/views/livewire/training/session-config-tab.blade.php --}}
<div class="space-y-4">
    @if ($trainingType === 'LMS')
        {{-- LMS: Participants first, room optional --}}
        <x-choices label="Select Participants" wire:model="participants" :options="$usersSearchable"
            search-function="userSearch" debounce="300ms" option-value="id" option-label="name"
            class="focus-within:border-0" placeholder="Search name of participant..." min-chars=2
            hint="Type at least 2 chars" searchable multiple clearable />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-input label="Room Name (Optional)" placeholder="Room name"
                class="focus-within:border-0" wire:model="room.name" />
            <x-input label="Room Location (Optional)" placeholder="Room location"
                class="focus-within:border-0" wire:model="room.location" />
        </div>
    @else
        {{-- Non-LMS: Full session config --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-choices label="Trainer" wire:model="trainerId" :options="$trainersSearchable"
                search-function="trainerSearch" debounce="300ms" option-value="id"
                option-label="name" placeholder="Search trainer..."
                class="focus-within:border-0" searchable single clearable />

            <x-choices label="Select Participants" wire:model="participants" :options="$usersSearchable"
                search-function="userSearch" debounce="300ms" option-value="id"
                option-label="name" class="focus-within:border-0"
                placeholder="Search participant..." min-chars=2 searchable multiple clearable />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-input label="Room Name" placeholder="Room name" class="focus-within:border-0"
                wire:model="room.name" />
            <x-input label="Room Location" placeholder="Room location"
                class="focus-within:border-0" wire:model="room.location" />
        </div>
        
        <div class="grid grid-cols-2 gap-5">
            <x-input type="time" label="Start Time" wire:model="startTime"
                class="focus-within:border-0" placeholder="HH:MM" />
            <x-input type="time" label="End Time" wire:model="endTime"
                class="focus-within:border-0" placeholder="HH:MM" />
        </div>
    @endif
</div>
```

#### Step 3: Verify and commit

```bash
php -l app/Livewire/Components/Training/TrainingForm/SessionConfigTab.php
git add app/Livewire/Components/Training/TrainingForm/
git add resources/views/livewire/training/session-config-tab.blade.php
git commit -m "feat(training): add SessionConfigTab component"
```

---

## Task 5: Create Alpine-Driven ScheduleFilters

**Files:**
- Create: `app/Livewire/Components/Training/Filters/ScheduleFilters.php`
- Create: `resources/views/livewire/training/schedule-filters.blade.php`

#### Step 1: Create minimal Livewire component (data only)

```php
<?php

namespace App\Livewire\Components\Training\Filters;

use App\Models\Trainer;
use Livewire\Component;

class ScheduleFilters extends Component
{
    public array $trainers = [];
    
    public function mount(): void
    {
        // Load trainers for filter dropdown
        $this->trainers = Trainer::with('user')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name ?: ($t->user?->name ?? 'Unknown')
            ])
            ->toArray();
    }
    
    public function render()
    {
        return view('livewire.training.schedule-filters');
    }
}
```

#### Step 2: Create Alpine-driven blade template

```blade
{{-- resources/views/livewire/training/schedule-filters.blade.php --}}
<div x-data="scheduleFilters(@js($trainers))" class="flex flex-wrap gap-3 items-center">
    {{-- Type Filter --}}
    <select x-model="selectedType" @change="applyFilters()"
        class="select select-bordered select-sm w-40">
        <option value="">All Types</option>
        <option value="IN">In-House</option>
        <option value="OUT">Out-House</option>
        <option value="LMS">LMS</option>
        <option value="BLENDED">Blended</option>
    </select>
    
    {{-- Trainer Filter --}}
    <select x-model="selectedTrainerId" @change="applyFilters()"
        class="select select-bordered select-sm w-48">
        <option value="">All Trainers</option>
        <template x-for="trainer in trainers" :key="trainer.id">
            <option :value="trainer.id" x-text="trainer.name"></option>
        </template>
    </select>
    
    {{-- Clear Button --}}
    <button x-show="selectedType || selectedTrainerId" 
        @click="clearFilters()"
        class="btn btn-ghost btn-sm">
        Clear
    </button>
    
    {{-- Active Filter Tags --}}
    <div x-show="selectedType || selectedTrainerId" class="flex gap-2">
        <template x-if="selectedType">
            <span class="badge badge-primary gap-1">
                <span x-text="'Type: ' + selectedType"></span>
                <button @click="selectedType = ''; applyFilters()">&times;</button>
            </span>
        </template>
        <template x-if="selectedTrainerId">
            <span class="badge badge-primary gap-1">
                <span x-text="'Trainer: ' + getTrainerName(selectedTrainerId)"></span>
                <button @click="selectedTrainerId = ''; applyFilters()">&times;</button>
            </span>
        </template>
    </div>
</div>

@script
<script>
function scheduleFilters(trainers) {
    return {
        trainers: trainers,
        selectedType: '',
        selectedTrainerId: '',
        
        applyFilters() {
            // Dispatch event for calendar to filter client-side
            this.$dispatch('filter-trainings', {
                type: this.selectedType,
                trainerId: this.selectedTrainerId
            });
        },
        
        clearFilters() {
            this.selectedType = '';
            this.selectedTrainerId = '';
            this.applyFilters();
        },
        
        getTrainerName(id) {
            const trainer = this.trainers.find(t => t.id == id);
            return trainer?.name || '';
        }
    }
}
</script>
@endscript
```

#### Step 3: Commit

```bash
git add app/Livewire/Components/Training/Filters/
git add resources/views/livewire/training/schedule-filters.blade.php
git commit -m "feat(training): add Alpine-driven ScheduleFilters component"
```

---

## Task 6: Create ScheduleCalendar Component (Replaces ScheduleView)

**Files:**
- Create: `app/Livewire/Components/Training/Calendar/ScheduleCalendar.php`
- Create: `resources/views/livewire/training/schedule-calendar.blade.php`

This task involves migrating the core calendar logic from ScheduleView with optimizations. Full implementation in the component.

#### Step 1: Create optimized calendar component

See design document for full implementation. Key changes:
- Client-side filtering via Alpine
- Eager load all relations upfront
- Cache counts aggressively
- Pass pre-computed data to Alpine for instant filtering

#### Step 2: Commit

```bash
git add app/Livewire/Components/Training/Calendar/
git add resources/views/livewire/training/schedule-calendar.blade.php
git commit -m "feat(training): add ScheduleCalendar with client-side filtering"
```

---

## Task 7: Update Page to Use New Components

**Files:**
- Modify: `resources/views/pages/training/training-schedule.blade.php`

#### Step 1: Update page to use new components

```blade
<div class="bg-white relative">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <h1 class="text-primary text-2xl sm:text-4xl font-bold">Training Schedule</h1>
        @role('admin')
            <div class="flex flex-wrap gap-2 items-center justify-center lg:justify-end">
                <livewire:components.training.filters.schedule-filters />
                <livewire:components.training.schedule-excel-actions />
                <livewire:components.training.training-form.training-form-modal />
            </div>
        @endrole
    </div>

    <livewire:components.training.calendar.schedule-calendar />
    
    @role('admin')
        <livewire:components.training.training-import-modal />
    @endrole
    <livewire:components.training.detail-training-modal />
    <livewire:components.confirm-dialog />
</div>
```

#### Step 2: Commit

```bash
git add resources/views/pages/training/training-schedule.blade.php
git commit -m "refactor(training): update page to use new component structure"
```

---

## Task 8: Cleanup Old Files

**Files:**
- Delete: `app/Livewire/Components/Training/ScheduleView.php` (after migration verified)
- Delete: `app/Livewire/Components/Training/FullCalendar.php` (after migration verified)
- Delete: `app/Livewire/Components/Training/TrainingFormModal.php` (old one)
- Delete: Old blade files

#### Step 1: Remove old files

```bash
rm app/Livewire/Components/Training/ScheduleView.php
rm app/Livewire/Components/Training/FullCalendar.php
rm app/Livewire/Components/Training/TrainingFormModal.php
git add -A
git commit -m "chore(training): remove old components after migration"
```

---

## Task 9: Integration Testing

**Verification Steps:**

1. Open Training Schedule page - should load without errors
2. Click a date on calendar - modal should open INSTANTLY with skeleton
3. Apply filter - should filter INSTANTLY (no delay)
4. Navigate months - should work smoothly
5. Create new training - should save correctly
6. Edit existing training - should load and save correctly
7. Delete training - should work correctly

---

## Summary

| Task | Description | Est. Time |
|------|-------------|-----------|
| 1 | Create TrainingFormState Trait | 15 min |
| 2 | Create New TrainingFormModal | 30 min |
| 3 | Create TrainingConfigTab | 20 min |
| 4 | Create SessionConfigTab | 20 min |
| 5 | Create ScheduleFilters | 15 min |
| 6 | Create ScheduleCalendar | 45 min |
| 7 | Update Page | 10 min |
| 8 | Cleanup Old Files | 5 min |
| 9 | Integration Testing | 20 min |
| **Total** | | **~3 hours** |
