<div>
    @if ($selectedEvent)
        <x-modal wire:model="modal" icon='o-document-text' title="Training Details"
            subtitle="Information about training details" separator
            box-class="w-[calc(100vw-2rem)] max-w-full sm:max-w-4xl h-fit">
            {{-- Tabs navigation --}}
            <div
                class="px-1 pt-2 mb-4 border-b border-gray-200 flex items-end justify-between gap-4 text-sm font-medium">
                <div class="flex gap-6">
                    <button type="button" wire:click="$set('activeTab','information')"
                        class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'information' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                        Information
                        @if ($activeTab === 'information')
                            <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                        @endif
                    </button>
                    <button type="button" wire:click="$set('activeTab','attendance')"
                        class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'attendance' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                        Attendance
                        @if ($activeTab === 'attendance')
                            <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                        @endif
                    </button>
                </div>
                <div class="flex items-center gap-2 pb-1">
                    <span class="text-xs text-gray-500">Day</span>
                    <x-select wire:model="dayNumber" :options="$trainingDates" option-label="name" option-value="id"
                        wire:change="$refresh" class="w-40 !h-8 !text-sm focus-within:outline-none" />
                </div>
            </div>

            @if ($activeTab === 'information')
                <livewire:components.training.tabs.training-information-tab :training-id="$selectedEvent['id']" :day-number="$dayNumber"
                    :key="'info-' . $selectedEvent['id'] . '-' . $dayNumber" lazy />
            @endif

            {{-- Attendance Section --}}
            @if ($activeTab === 'attendance')
                <livewire:components.training.tabs.training-attendance-tab :training-id="$selectedEvent['id']" :day-number="$dayNumber"
                    :key="'att-' . $selectedEvent['id'] . '-' . $dayNumber" lazy />
            @endif

            <div class="flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3 mt-5">
                <x-button wire:click="closeModal"
                    class="btn btn-error bg-white hover:bg-red-100 hover:opacity-80 w-full sm:w-auto">Close</x-button>
                <div class="flex items-center sm:items-center justify-center gap-3 w-full sm:w-auto">
                    <x-button wire:click="requestDeleteConfirm" class="btn-error w-fit sm:w-auto"
                        spinner="requestDeleteConfirm">
                        <x-icon name="o-trash" /><span class="">Delete</span>
                    </x-button>
                </div>
            </div>
            <div class="mt-4 text-xs text-gray-500 text-right">Deleting will remove all days, sessions, and attendances.
            </div>
        </x-modal>
    @endif
</div>
