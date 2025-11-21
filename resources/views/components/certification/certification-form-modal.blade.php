<div>
    <!-- Trigger Button -->
    <x-ui.button wire:click="openModal" variant="primary">
        <x-icon name="o-plus" class="w-5 h-5" />
        Add <span class="hidden sm:block">New Certification</span>
    </x-ui.button>

    <!-- Modal -->
    <x-modal wire:model="showModal" :title="$isEdit ? 'Edit Certification' : 'New Certification'" :subtitle="$isEdit ? 'Modify existing certification and sessions' : 'Create certification and sessions'" box-class="backdrop-blur max-w-3xl">
        <div class="space-y-6">
            @php $theoryDate = $theory['date'] ?? null; $practicalDate = $practical['date'] ?? null; @endphp
            @if (empty($theoryDate) || empty($practicalDate))
                <div class="p-3 border border-amber-200 bg-amber-50 rounded-md text-amber-800 text-sm flex items-start gap-2">
                    <x-icon name="o-exclamation-triangle" class="w-4 h-4 mt-0.5" />
                    <div>
                        <span class="font-semibold">Session config incomplete.</span>
                        <div class="text-amber-700">Please set both Theory and Practical dates in the Session Config tab before saving.</div>
                    </div>
                </div>
            @endif
            <x-tabs wire:model="activeTab">
                <x-tab name="config" label="Certification Config" icon="o-academic-cap">
                    <div class="space-y-4">
                        <!-- Module and Name -->
                        <x-select label="Certification Module" wire:model="module_id" wire:change="syncNameFromModule" :options="$moduleOptions" option-value="id" option-label="title" placeholder="Select module" icon="o-rectangle-group" />
                        <x-input wire:model="certification_name" label="Certification Name" placeholder="Auto-filled from module, or customize as needed" class="focus-within:border-0" />
                        @if($module_id)
                            <div class="flex items-center gap-2">
                                <x-icon name="o-information-circle" class="w-4 h-4 text-primary/70" />
                                <span class="text-xs text-gray-500">Auto-filled from module. If you modify it, future module changes won’t overwrite.</span>
                            </div>
                        @endif

                        <!-- Participants -->
                        <div class="space-y-2">
                            <x-choices label="Participants" wire:model="participants" :options="$usersSearchable" search-function="userSearch" debounce="300ms" option-value="id" option-label="name" class="focus-within:border-0" placeholder="Search name of participant..." min-chars=2 hint="Type at least 2 chars" searchable multiple clearable />
                        </div>
                    </div>
                </x-tab>

                <x-tab name="session" label="Session Config" icon="o-cog-6-tooth">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Theory session (always required) -->
                        <div class="p-4 border rounded-md space-y-4 mt-3">
                            <p class="font-semibold text-gray-800">Theory Session</p>
                            <x-datepicker wire:model.defer="theory.date" placeholder="Select date" icon="o-calendar" class="w-full" label="Date" :config="['mode' => 'single','altInput' => true,'altFormat' => 'd M Y','dateFormat' => 'Y-m-d']" />
                            <div class="grid grid-cols-2 gap-4">
                                <x-input type="time" label="Start Time" wire:model="theory.start_time" class="focus-within:border-0" placeholder="HH:MM" />
                                <x-input type="time" label="End Time" wire:model="theory.end_time" class="focus-within:border-0" placeholder="HH:MM" />
                            </div>
                            <x-input label="Location" placeholder="Room or area" class="focus-within:border-0" wire:model="theory.location" />
                        </div>
                        <!-- Practical session (now required) -->
                        <div class="p-4 border rounded-md space-y-4 mt-3">
                            <p class="font-semibold text-gray-800">Practical Session</p>
                            <x-datepicker wire:model.defer="practical.date" placeholder="Select date" icon="o-calendar" class="w-full" label="Date" :config="['mode' => 'single','altInput' => true,'altFormat' => 'd M Y','dateFormat' => 'Y-m-d']" />
                            <div class="grid grid-cols-2 gap-4">
                                <x-input type="time" label="Start Time" wire:model="practical.start_time" class="focus-within:border-0" placeholder="HH:MM" />
                                <x-input type="time" label="End Time" wire:model="practical.end_time" class="focus-within:border-0" placeholder="HH:MM" />
                            </div>
                            <x-input label="Location" placeholder="Room or area" class="focus-within:border-0" wire:model="practical.location" />
                        </div>
                    </div>
                </x-tab>
            </x-tabs>
        </div>

        <!-- Modal Actions -->
        <x-slot:actions>
            <x-button label="Cancel" wire:click="closeModal" class="btn-ghost" />
            <x-button :label="$isEdit ? 'Update Certification' : 'Save Certification'" wire:click="save" class="btn-primary" spinner="save" title="Fix the validation errors first" />
        </x-slot:actions>
    </x-modal>
</div>
