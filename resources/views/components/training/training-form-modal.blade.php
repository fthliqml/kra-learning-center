<div>
    <!-- Trigger Button -->
    <x-ui.button wire:click="openModal" variant="primary">
        <x-icon name="o-plus" class="w-5 h-5" />
        Add <span class="hidden sm:block">New Training</span>
    </x-ui.button>

    <!-- Modal -->
    <x-modal wire:model="showModal" :title="$isEdit ? 'Edit Training' : 'New Training'" :subtitle="$isEdit ? 'Modify existing training' : 'Creating a new training'" box-class="backdrop-blur max-w-4xl">
        <div class="space-y-6">
            @if ($showTypeChangeConfirm)
                <div class="p-4 rounded-md border border-amber-300 bg-amber-50 text-amber-800 text-sm space-y-2">
                    <p class="font-semibold">Confirm Training Type Change</p>
                    <p>Switching to <strong>K-LEARN</strong> will remove all trainer & session time fields and
                        <strong>delete existing attendance records</strong> when you save. Participants (assessments)
                        will stay.
                    </p>
                    <div class="flex gap-2">
                        <x-button label="Cancel" class="btn-ghost btn-sm" wire:click="cancelTypeChange" />
                        <x-button label="Yes, switch to K-LEARN" class="btn-warning btn-sm"
                            wire:click="confirmTypeChange" />
                    </div>
                </div>
            @endif
            <!-- Tabs Navigation -->
            <x-tabs wire:model="activeTab">
                <x-tab name="training" label="Training Config" icon="o-academic-cap">
                    <div class="{{ $errors->any() ? 'space-y-1' : 'space-y-4' }}">
                        <!-- Training Name / Course Select for K-LEARN -->
                        @if ($training_type === 'K-LEARN')
                            <x-select label="Course" wire:model="course_id" :options="$courseOptions" option-value="id"
                                option-label="title" placeholder="Select course" icon="o-rectangle-group" />
                            @if ($training_name)
                                <p class="text-xs text-gray-500 -mt-2">Selected Course Title: <span
                                        class="font-medium">{{ $training_name }}</span></p>
                            @endif
                        @else
                            <x-input wire:model="training_name" label="Training Name" placeholder="Enter training name"
                                class="focus-within:border-0" />
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center justify-center w-full">
                            <!-- Type -->
                            <x-select label="Training Type" wire:model="training_type" :options="$trainingTypeOptions"
                                option-value="id" option-label="name" icon="o-book-open" wire:change="$refresh" />

                            <!-- Group Comp -->
                            <div>
                                <x-select label="Group Competency" wire:model="group_comp" :options="$groupCompOptions"
                                    option-value="id" option-label="name" icon="o-clipboard-document"
                                    :disabled="$training_type === 'K-LEARN'" />
                                @if ($training_type === 'K-LEARN')
                                    <p class="text-xs text-gray-500 mt-1">Synced from selected Course.</p>
                                @endif
                            </div>
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
                        @if ($training_type === 'K-LEARN')
                            <!-- Participants first for K-LEARN -->
                            <x-choices label="Select Participants" wire:model="participants" :options="$usersSearchable"
                                search-function="userSearch" debounce="300ms" option-value="id" option-label="name"
                                class="focus-within:border-0" placeholder="Search name of participant..." min-chars=2
                                hint="Type at least 2 chars" searchable multiple clearable />

                            <!-- Room fields immediately after participants -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center w-full mt-2">
                                <x-input :label="$training_type === 'K-LEARN' ? 'Room Name (Optional)' : 'Room Name'" placeholder="Room name" class="focus-within:border-0"
                                    wire:model="room.name" />
                                <x-input :label="$training_type === 'K-LEARN' ? 'Room Location/Office (Optional)' : 'Room Location/Office'" placeholder="Room location" class="focus-within:border-0"
                                    wire:model="room.location" />
                            </div>

                            <div
                                class="p-3 rounded-md bg-amber-50 border border-amber-200 text-amber-700 text-xs leading-relaxed mt-2">
                                K-LEARN does not require Trainer or Session Times. The fields below are for reference
                                only and are disabled.
                            </div>

                            <div class="mt-2 space-y-4">
                                <div class="opacity-50 pointer-events-none">
                                    <x-choices label="Trainer" wire:model="trainerId" :options="$trainersSearchable"
                                        search-function="trainerSearch" debounce="300ms" option-value="id"
                                        option-label="name" placeholder="Search trainer name..."
                                        class="focus-within:border-0" min-chars=2 hint="Type at least 2 chars"
                                        searchable single clearable />
                                </div>
                                <div class="grid grid-cols-2 gap-5 items-center w-full opacity-50 pointer-events-none">
                                    <x-input type="time" label="Start Time" wire:model="start_time"
                                        class="focus-within:border-0" placeholder="HH:MM" />
                                    <x-input type="time" label="End Time" wire:model="end_time"
                                        class="focus-within:border-0" placeholder="HH:MM" />
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center justify-center w-full">
                                <!-- Trainer -->
                                <x-choices label="Trainer" wire:model="trainerId" :options="$trainersSearchable"
                                    search-function="trainerSearch" debounce="300ms" option-value="id"
                                    option-label="name" placeholder="Search trainer name..."
                                    class="focus-within:border-0" min-chars=2 hint="Type at least 2 chars" searchable
                                    single clearable />

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
            <x-button label="Cancel" wire:click="closeModal" class="btn-ghost" />
            <x-button :label="$isEdit ? 'Update Training' : 'Save Training'" wire:click="saveTraining" class="btn-primary" spinner="saveTraining"
                title="Fix the validation errors first" />
        </x-slot:actions>
    </x-modal>

</div>
