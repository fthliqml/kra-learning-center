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
                    @anyrole('admin', 'instructor')
                        <button type="button" wire:click="$set('activeTab','attendance')"
                            class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'attendance' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                            Attendance
                            @if ($activeTab === 'attendance')
                                <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                            @endif
                        </button>
                    @endanyrole
                </div>
                <div class="flex items-center gap-3 pb-1">
                    @php
                        $type = strtoupper($selectedEvent['type'] ?? '');
                        switch ($type) {
                            case 'IN':
                                $badge = 'bg-green-100 text-green-700 border border-green-300';
                                break;
                            case 'OUT':
                                $badge = 'bg-amber-100 text-amber-700 border border-amber-300';
                                break;
                            case 'K-LEARN':
                            case 'KLEARN':
                            case 'KLEARNING':
                                $badge = 'bg-indigo-100 text-indigo-700 border border-indigo-300';
                                break;
                            default:
                                $badge = 'bg-blue-100 text-blue-700 border border-blue-300';
                        }
                    @endphp
                    @if ($type)
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold tracking-wide {{ $badge }}">
                            {{ $type }}
                        </span>
                    @endif
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Day</span>
                        <x-select wire:model="dayNumber" :options="$trainingDates" option-label="name" option-value="id"
                            wire:change="$refresh" class="w-40 !h-8 !text-sm focus-within:outline-none" />
                    </div>
                </div>
            </div>

            @if ($activeTab === 'information')
                <livewire:components.training.tabs.training-information-tab :training-id="$selectedEvent['id']" :day-number="$dayNumber"
                    :key="'info-' . $selectedEvent['id'] . '-' . $dayNumber" />
            @endif

            @anyrole('admin', 'instructor')
                {{-- Attendance Section --}}
                @if ($activeTab === 'attendance')
                    <livewire:components.training.tabs.training-attendance-tab :training-id="$selectedEvent['id']" :day-number="$dayNumber"
                        :key="'att-' . $selectedEvent['id'] . '-' . $dayNumber" lazy />
                @endif
            @endanyrole

            <div class="flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3 mt-5">
                @anyrole('admin', 'instructor')
                    @php
                        $typeUpperFooter = strtoupper(
                            $selectedEvent['type'] ?? ($selectedEvent['training_type'] ?? ''),
                        );
                        $isDoneFooter = strtolower($selectedEvent['status'] ?? '') === 'done';
                    @endphp
                    @if (in_array($typeUpperFooter, ['IN', 'OUT']) && !$isDoneFooter)
                        <x-button wire:click="closeTraining" spinner="closeTraining"
                            class="btn btn-primary w-full sm:w-auto">Close Training</x-button>
                    @else
                        <x-button wire:click="closeModal"
                            class="btn bg-white hover:bg-gray-100 hover:opacity-80 w-full sm:w-auto">Close</x-button>
                    @endif
                @else
                    <x-button wire:click="closeModal"
                        class="btn bg-white hover:bg-gray-100 hover:opacity-80 w-full sm:w-auto">Close</x-button>
                @endanyrole
                @role('admin')
                    <div class="flex items-center sm:items-center justify-center gap-3 w-full sm:w-auto">
                        <x-button wire:click="requestDeleteConfirm" class="btn-error w-fit sm:w-auto"
                            spinner="requestDeleteConfirm">
                            <x-icon name="o-trash" /><span class="">Delete</span>
                        </x-button>
                    </div>
                @endrole
            </div>
            @role('admin')
                <div class="mt-4 text-xs text-gray-500 text-right">Deleting will remove all days, sessions, and attendances.
                </div>
            @endrole
        </x-modal>
    @endif
</div>
