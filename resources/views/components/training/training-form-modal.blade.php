<div>
    <!-- Trigger Button -->
    <x-ui.button wire:click="openModal" variant="primary">
        <x-icon name="o-plus" class="w-5 h-5" />
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
                <x-tab name="training" label="Training Config" icon="o-academic-cap">
                    <div class="{{ $errors->any() ? 'space-y-1' : 'space-y-4' }}">
                        <!-- Training Type -->
                        <x-choices label="Training Type" wire:model.live="training_type" :options="$trainingTypeOptions"
                            option-value="id" option-label="name" icon="o-book-open" single
                            class="focus-within:border-0" />

                        <!-- Training Name / Course Select for LMS / Module Select for In-House -->
                        @if ($training_type === 'LMS')
                            <x-choices label="Course" wire:model.live="selected_module_id" :options="$courseOptions"
                                option-value="id" option-label="title" placeholder="Select course"
                                icon="o-rectangle-group" single searchable class="focus-within:border-0"
                                search-function="searchCourse" debounce="300ms"
                                wire:key="course-select-{{ $isEdit ? $trainingId : 'new' }}" />
                        @elseif ($training_type === 'IN')
                            <x-choices label="Training Module" wire:model.live="selected_module_id" :options="$trainingModuleOptions"
                                option-value="id" option-label="title" placeholder="Select training module"
                                icon="o-academic-cap" single searchable class="focus-within:border-0"
                                search-function="searchTrainingModule" debounce="300ms"
                                wire:key="module-select-{{ $isEdit ? $trainingId : 'new' }}-{{ $selected_module_id }}" />
                            <x-input wire:model.live="training_name" label="Training Name (Optional)"
                                placeholder="Edit training name or leave as selected module"
                                class="focus-within:border-0" hint="You can customize the training name" />
                        @else
                            <x-input wire:model="training_name" label="Training Name" placeholder="Enter training name"
                                class="focus-within:border-0" />
                        @endif

                        <!-- Group Comp -->
                        <div>
                            @if ($training_type === 'LMS' || $training_type === 'IN')
                                <x-input label="Group Competency" wire:model="group_comp" readonly
                                    icon="o-clipboard-document" class="focus-within:border-0 bg-gray-50"
                                    hint="{{ $training_type === 'LMS' ? 'Synced from selected Course' : 'Synced from selected Training Module' }}" />
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

                <x-tab name="session" label="Session Config" icon="o-cog-6-tooth">
                    <div class="{{ $errors->any() ? 'space-y-1' : 'space-y-4' }}">
                        @if ($training_type === 'LMS')
                            <!-- Participants first for LMS -->
                            <x-choices label="Select Participants" wire:model="participants" :options="$usersSearchable"
                                search-function="userSearch" debounce="300ms" option-value="id" option-label="name"
                                class="focus-within:border-0" placeholder="Search name of participant..." min-chars=2
                                hint="Type at least 2 chars" searchable multiple clearable />

                            <!-- Room fields immediately after participants -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center w-full mt-2">
                                <x-input label="Room Name (Optional)" placeholder="Room name"
                                    class="focus-within:border-0" wire:model="room.name" />
                                <x-input label="Room Location/Office (Optional)" placeholder="Room location"
                                    class="focus-within:border-0" wire:model="room.location" />
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center justify-center w-full">
                                <!-- Trainer -->
                                <x-choices label="Trainer" wire:model="trainerId" :options="$trainersSearchable"
                                    search-function="trainerSearch" debounce="300ms" option-value="id"
                                    option-label="name" placeholder="Search name of trainer..." class="focus-within:border-0"
                                    hint="Type at least 2 chars" searchable single clearable />

                                <!-- Participants Section -->
                                <x-choices label="Select Participants" wire:model="participants" :options="$usersSearchable"
                                    search-function="userSearch" debounce="300ms" option-value="id"
                                    option-label="name" class="focus-within:border-0"
                                    placeholder="Search name of participant..." min-chars=2
                                    hint="Type at least 2 chars" searchable multiple clearable />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center w-full">
                                <x-input label="Room Name" placeholder="Room name" class="focus-within:border-0"
                                    wire:model="room.name" />
                                <x-input label="Room Location/Office" placeholder="Room location"
                                    class="focus-within:border-0" wire:model="room.location" />
                            </div>
                            <div class="grid grid-cols-2 gap-5 items-center w-full">
                                <x-input type="time" label="Start Time" wire:model="start_time"
                                    class="focus-within:border-0" placeholder="HH:MM" />
                                <x-input type="time" label="End Time" wire:model="end_time"
                                    class="focus-within:border-0" placeholder="HH:MM" />
                            </div>
                        @endif
                    </div>
                </x-tab>
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
