<div>
    <!-- Trigger Button -->
    <x-ui.button wire:click="openModal" variant="primary" wire:loading.attr="disabled">
        <span wire:loading wire:target="openModal" class="loading loading-spinner loading-xs"></span>
        <x-icon name="o-plus" class="w-5 h-5" wire:loading.remove wire:target="openModal" />
        Add <span class="hidden sm:block">New Training</span>
    </x-ui.button>

    <!-- Modal -->
    <x-modal wire:model="showModal" :title="$isEdit ? 'Edit Training' : 'New Training'" :subtitle="$isEdit ? 'Modify existing training' : 'Creating a new training'" box-class="backdrop-blur max-w-4xl">
        
        <div class="space-y-6">
            @if (session('info_module'))
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm text-amber-700">
                        <strong>Note:</strong> {{ session('info_module') }}
                    </p>
                </div>
            @endif
            @if ($showTypeChangeConfirm)
                <div class="p-4 rounded-md border border-amber-300 bg-amber-50 text-amber-800 text-sm space-y-2">
                    <p class="font-semibold">Confirm Training Type Change</p>
                    <p>Switching to <strong>LMS</strong> will remove all trainer & session time fields and
                        <strong>delete existing attendance records</strong> when you save. Participants (assessments)
                        will stay.
                    </p>
                    <div class="flex gap-2">
                        <x-button label="Cancel" class="btn-ghost btn-sm" wire:click="cancelTypeChange" />
                        <x-button label="Yes, switch to LMS" class="btn-warning btn-sm"
                            wire:click="confirmTypeChange" />
                    </div>
                </div>
            @endif
            <!-- Tabs Navigation -->
            <x-tabs wire:model="activeTab">
                {{-- Tab 1: Training Config --}}
                <x-tab name="training" label="Training Config" icon="o-academic-cap">
                    <div class="{{ $errors->any() ? 'space-y-1' : 'space-y-4' }}">
                        <!-- Training Type -->
                        <x-choices label="Training Type" wire:model.live="training_type" :options="$trainingTypeOptions"
                            option-value="id" option-label="name" icon="o-book-open" single
                            class="focus-within:border-0" />

                        {{-- Training Name / Course Select for LMS and BLENDED / Module Select for In-House --}}
                        @if ($training_type === 'LMS' || $training_type === 'BLENDED')
                            <div wire:key="course-select-wrap-{{ $isEdit ? $trainingId : 'new' }}-{{ $selected_module_id }}">
                                <x-choices label="Course" wire:model.live="selected_module_id" :options="$courseOptions"
                                    option-value="id" option-label="title" placeholder="Select course"
                                    icon="o-rectangle-group" single searchable class="focus-within:border-0"
                                    search-function="searchCourse" debounce="300ms" min-chars="0" />
                            </div>
                        @elseif ($training_type === 'IN')
                            <x-choices label="Training Module" wire:model.live="selected_module_id" :options="$trainingModuleOptions"
                                option-value="id" option-label="title" placeholder="Select training module"
                                icon="o-academic-cap" single searchable class="focus-within:border-0"
                                search-function="searchTrainingModule" debounce="300ms"
                                wire:key="module-select-{{ $isEdit ? $trainingId : 'new' }}-{{ $selected_module_id }}" />
                            <x-input wire:model.live="training_name" label="Training Name (Optional)"
                                placeholder="Edit training name or leave as selected module"
                                class="focus-within:border-0" hint="You can customize the training name" />
                        @elseif ($training_type === 'OUT')
                            <div
                                wire:key="competency-select-wrap-{{ $isEdit ? $trainingId : 'new' }}-{{ $competency_id }}">
                                <x-choices label="Competency" wire:model.live="competency_id" :options="$competencyOptions"
                                    option-value="id" option-label="name" placeholder="Select competency"
                                    icon="o-academic-cap" single searchable class="focus-within:border-0"
                                    search-function="searchCompetency" debounce="300ms"
                                    @change-selection="const v = $event.detail?.value; if (v === null || v === undefined || v === '') return; $wire.set('competency_id', v)" />
                            </div>
                            <x-input wire:model.live="training_name" label="Training Name (Optional)"
                                placeholder="Auto-filled from selected competency (optional)"
                                class="focus-within:border-0" hint="Auto-filled from selected competency" />
                        @else
                            <x-input wire:model="training_name" label="Training Name" placeholder="Enter training name"
                                class="focus-within:border-0" />
                        @endif

                        <!-- Group Comp -->
                        <div>
                            @if ($training_type === 'LMS' || $training_type === 'IN' || $training_type === 'BLENDED')
                                <x-input label="Group Competency" wire:model="group_comp" readonly
                                    icon="o-clipboard-document" class="focus-within:border-0 bg-gray-50"
                                    hint="{{ in_array($training_type, ['LMS', 'BLENDED']) ? 'Synced from selected Course' : 'Synced from selected Training Module' }}" />
                            @elseif ($training_type === 'OUT')
                                <x-input label="Group Competency" wire:model="group_comp" readonly
                                    icon="o-clipboard-document" class="focus-within:border-0 bg-gray-50"
                                    hint="Auto-filled from selected competency" />
                            @else
                                <x-choices label="Group Competency" wire:model="group_comp" :options="$groupCompOptions"
                                    option-value="id" option-label="name" icon="o-clipboard-document" single
                                    class="focus-within:border-0" />
                            @endif
                        </div>

                        <!-- Date Range -->
                        <x-datepicker wire:model.defer="date" placeholder="Select date range" icon="o-calendar"
                            class="w-full" label="Training Date" :config="[
                                'mode' => 'range',
                                'altInput' => true,
                                'altFormat' => 'd M Y',
                            ]" />
                    </div>
                </x-tab>

                {{-- Tab 2: Trainer & Participants --}}
                <x-tab name="personnel" label="Trainer & Participants" icon="o-users">
                    <div class="{{ $errors->any() ? 'space-y-1' : 'space-y-4' }}">
                        {{-- Trainer (disabled for LMS) --}}
                        @if ($training_type === 'LMS')
                            <div class="opacity-50">
                                <x-choices label="Trainer" :options="[]"
                                    placeholder="Not applicable for LMS training"
                                    class="focus-within:border-0" disabled single
                                    hint="LMS trainings don't require a trainer" />
                            </div>
                        @else
                            <x-choices label="Trainer" wire:model="trainerId" :options="$trainersSearchable"
                                search-function="trainerSearch" debounce="300ms" option-value="id"
                                option-label="name" placeholder="Search name of trainer..."
                                class="focus-within:border-0" hint="Same trainer for all days" searchable single
                                clearable />
                        @endif

                        {{-- Participants --}}
                        <x-choices label="Select Participants" wire:model="participants" :options="$usersSearchable"
                            search-function="userSearch" debounce="300ms" option-value="id" option-label="name"
                            class="focus-within:border-0" placeholder="Search name of participant..." min-chars=2
                            hint="Type at least 2 chars" searchable multiple clearable />
                    </div>
                </x-tab>

                {{-- Tab 3: Time & Room (hidden for LMS) --}}
                @if ($training_type !== 'LMS')
                <x-tab name="schedule" label="Time & Room" icon="o-clock">
                    <div class="{{ $errors->any() ? 'space-y-1' : 'space-y-4' }}">
                        {{-- Per-Day Settings Toggle --}}
                        @php
                            $totalDays = $this->getTotalDays();
                            $dayOptions = $this->getTrainingDayOptions();
                        @endphp
                        
                        @if ($totalDays > 1)
                            <div class="p-3 bg-base-200 rounded-lg border border-base-300">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" 
                                        wire:click="toggleApplyToAllDays"
                                        @checked($applyToAllDays)
                                        class="checkbox checkbox-primary checkbox-sm" />
                                    <span class="text-sm font-medium">Apply same room & time settings to all days</span>
                                </label>
                                @if (!$applyToAllDays)
                                    <p class="text-xs text-base-content/60 mt-1 ml-7">
                                        Configure different room and time for each day below
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- Per-Day Configuration (when toggle is OFF) --}}
                        @if (!$applyToAllDays && $totalDays > 1)
                            {{-- Day Selector --}}
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <x-select 
                                    label="Select Day to Configure" 
                                    wire:model.live="selectedDayNumber" 
                                    :options="$dayOptions"
                                    option-label="name"
                                    option-value="id"
                                    class="flex-1"
                                />
                            </div>

                            {{-- Day Navigation Dots - Wrapped in styled container --}}
                            <div class="p-4 bg-base-200/50 rounded-lg border border-base-300 space-y-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-medium text-base-content/70">Quick nav:</span>
                                    @for ($d = 1; $d <= $totalDays; $d++)
                                        <button 
                                            type="button"
                                            wire:click="loadDayConfig({{ $d }})"
                                            class="w-9 h-9 rounded-full text-xs font-medium flex items-center justify-center transition-all shadow-sm
                                            {{ $selectedDayNumber === $d ? 'ring-2 ring-primary ring-offset-2 ring-offset-base-200' : '' }}
                                            {{ $this->dayHasOverride($d) ? 'bg-primary text-primary-content hover:bg-primary-focus' : 'bg-base-100 text-base-content border border-base-300 hover:bg-base-300' }}"
                                            title="{{ $this->dayHasOverride($d) ? 'Day ' . $d . ' has custom settings' : 'Day ' . $d . ' uses default settings' }}"
                                        >
                                            {{ $d }}
                                        </button>
                                    @endfor
                                </div>
                                <div class="flex items-center gap-6 text-xs text-base-content/60 pt-1">
                                    <span class="flex items-center gap-1.5">
                                        <span class="inline-block w-3 h-3 rounded-full bg-primary shadow-sm"></span> 
                                        <span>Customized</span>
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <span class="inline-block w-3 h-3 rounded-full bg-base-100 border border-base-300 shadow-sm"></span> 
                                        <span>Default (same as Day 1)</span>
                                    </span>
                                </div>
                            </div>
                        @endif

                        {{-- Room & Time Fields --}}
                        <div class="p-4 bg-base-100 rounded-lg border border-base-200 space-y-4">
                            @if (!$applyToAllDays && $totalDays > 1)
                                <div class="text-sm font-medium text-base-content/80 mb-2">
                                    Settings for Day {{ $selectedDayNumber }}
                                    @if ($selectedDayNumber === 1)
                                        <span class="text-xs font-normal text-base-content/50">(Reference day - other days inherit from here)</span>
                                    @else
                                        <span class="text-xs font-normal text-primary ml-1">(Auto-saved when you switch days)</span>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center w-full">
                                <x-input label="Room Name" placeholder="Room name" class="focus-within:border-0"
                                    wire:model.live.debounce.500ms="room.name" />
                                <x-input label="Room Location/Office" placeholder="Room location"
                                    class="focus-within:border-0" wire:model.live.debounce.500ms="room.location" />
                            </div>
                            <div class="grid grid-cols-2 gap-5 items-center w-full">
                                <x-input type="time" label="Start Time" wire:model.live.debounce.500ms="start_time"
                                    class="focus-within:border-0" placeholder="HH:MM" />
                                <x-input type="time" label="End Time" wire:model.live.debounce.500ms="end_time"
                                    class="focus-within:border-0" placeholder="HH:MM" />
                            </div>
                        </div>
                    </div>
                </x-tab>
                @endif
            </x-tabs>
        </div>

        <!-- Modal Actions -->
        <x-slot:actions>
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 w-full">
                <x-button label="Cancel" wire:click="closeModal" class="btn-ghost" />
                <div class="flex gap-2 justify-end">
                    @if ($isEdit)
                        @role('admin')
                            <x-button wire:click="requestDeleteConfirm" class="btn-error" spinner="requestDeleteConfirm">
                                <x-icon name="o-trash" />
                                <span>Delete</span>
                            </x-button>
                        @endrole
                    @endif
                    <x-button :label="$isEdit ? 'Update Training' : 'Save Training'" wire:click="saveTraining" class="btn-primary" spinner="saveTraining"
                        title="Fix the validation errors first" />
                </div>
            </div>
        </x-slot:actions>
    </x-modal>

</div>
