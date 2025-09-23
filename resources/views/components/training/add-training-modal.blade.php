<div>
    <!-- Trigger Button -->
    <x-ui.button wire:click="openModal" variant="primary">
        <x-icon name="o-plus" class="w-5 h-5" />
        Add New Training
    </x-ui.button>

    <!-- Modal -->
    <x-modal wire:model="showModal" title="New Training" subtitle="Creating a new training"
        box-class="backdrop-blur max-w-4xl">
        <div class="space-y-6 h-[400px]">
            <!-- Tabs Navigation -->
            <x-tabs wire:model="activeTab">
                <x-tab name="training" label="Training Config" icon="o-academic-cap">
                    <div class="space-y-6">
                        <!-- Training Name -->
                        <x-input wire:model="training_name" label="Training Name" placeholder="Enter training name"
                            class="focus-within:border-0" />

                        <!-- Date Range -->
                        <x-datepicker wire:model.defer="date" placeholder="Select date range" icon="o-calendar"
                            class="w-full" label="Training Date" :config="[
                                'mode' => 'range',
                                'altInput' => true,
                                'altFormat' => 'd M Y',
                            ]" />

                        <!-- Participants Section -->
                        <x-choices label="Select Participants" wire:model="participants" :options="$usersSearchable"
                            search-function="userSearch" debounce="300ms" option-value="id" option-label="name"
                            class="focus-within:border-0" placeholder="Search name of participant..." min-chars=2
                            hint="Type at least 2 chars" searchable multiple clearable />
                    </div>
                </x-tab>

                <x-tab name="session" label="Session Config" icon="o-cog-6-tooth">
                    <!-- Trainer -->
                    <x-choices label="Trainer" wire:model="trainer" :options="$trainersSearchable" search-function="trainerSearch"
                        debounce="300ms" option-value="id" option-label="name" placeholder="Search trainer name..."
                        class="focus-within:border-0" min-chars=2 hint="Type at least 2 chars" searchable single
                        clearable>
                    </x-choices>

                    <div class="space-y-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-center w-full">
                            <x-input label="Room Name" placeholder="Room name" class="focus-within:border-0"
                                wire:model="room.name" />
                            <x-input label="Room Location/Office" placeholder="Room location"
                                class="focus-within:border-0" wire:model="room.location" />
                        </div>
                    </div>
                </x-tab>
            </x-tabs>
        </div>

        <!-- Modal Actions -->
        <x-slot:actions>
            <x-button label="Cancel" wire:click="closeModal" class="btn-ghost" />
            <x-button label="Save Training" wire:click="saveTraining" class="btn-primary" spinner="saveTraining" />
        </x-slot:actions>
    </x-modal>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div class="alert alert-success mt-4">
            <x-icon name="o-check-circle" class="w-6 h-6" />
            {{ session('message') }}
        </div>
    @endif
</div>
