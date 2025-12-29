<div>
    @if ($selectedEvent)
        <x-modal wire:model="modal" icon='o-document-text' title="Training Details"
            subtitle="Information about training details" separator
            box-class="w-[calc(100vw-2rem)] max-w-full sm:max-w-4xl h-fit">
            @if (in_array(strtolower($selectedEvent['status'] ?? ''), ['approved', 'rejected', 'done']))
                <div
                    class="mt-3 mb-3 p-3 rounded-lg {{ strtolower($selectedEvent['status'] ?? '') === 'approved' ? 'bg-green-50 border border-green-200' : (strtolower($selectedEvent['status'] ?? '') === 'rejected' ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200') }}">
                    <p
                        class="text-sm {{ strtolower($selectedEvent['status'] ?? '') === 'approved' ? 'text-green-700' : (strtolower($selectedEvent['status'] ?? '') === 'rejected' ? 'text-red-700' : 'text-blue-700') }}">
                        <strong>Training {{ ucfirst(strtolower($selectedEvent['status'] ?? '')) }}:</strong>
                        @if (strtolower($selectedEvent['status'] ?? '') === 'approved')
                            Certificates are available.
                        @elseif(strtolower($selectedEvent['status'] ?? '') === 'rejected')
                            This training has been rejected.
                        @else
                            Waiting for approval.
                        @endif
                    </p>
                </div>
            @endif
            {{-- Tabs navigation --}}
            <div
                class="px-1 pt-2 mb-4 border-b border-gray-200 flex items-end justify-between gap-4 text-sm font-medium">
                <div class="flex gap-6">
                    @php
                        $isLms = strtoupper($selectedEvent['type'] ?? '') === 'LMS';
                    @endphp
                    <button type="button" wire:click="$set('activeTab','information')"
                        class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'information' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                        Information
                        @if ($activeTab === 'information')
                            <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                        @endif
                    </button>
                    @anyrole('admin', 'instructor')
                        @if (!$isLms)
                            <button type="button" wire:click="$set('activeTab','attendance')"
                                class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'attendance' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                                Attendance
                                @if ($activeTab === 'attendance')
                                    <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                                @endif
                            </button>
                        @endif
                        <button type="button" wire:click="$set('activeTab','close-training')"
                            class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'close-training' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                            Close Training
                            @if ($activeTab === 'close-training')
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
                            case 'LMS':
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
                @if (!$isLms && $activeTab === 'attendance')
                    <livewire:components.training.tabs.training-attendance-tab :training-id="$selectedEvent['id']" :day-number="$dayNumber"
                        :key="'att-' . $selectedEvent['id'] . '-' . $dayNumber" lazy />
                @endif

                {{-- Close Training Section --}}
                @if ($activeTab === 'close-training')
                    <livewire:components.training.tabs.training-close-tab :training-id="$selectedEvent['id']" :key="'close-' . $selectedEvent['id']" lazy />
                @endif
            @endanyrole

            <div class="flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3 mt-5">
                <x-button wire:click="closeModal"
                    class="btn bg-white hover:bg-gray-100 hover:opacity-80 w-full sm:w-auto">Close</x-button>
                @role('admin')
                    @php
                        $trainingStatus = strtolower($selectedEvent['status'] ?? '');
                        $canCloseTraining = !in_array($trainingStatus, ['done', 'approved', 'rejected']);
                    @endphp
                    @if ($activeTab === 'close-training' && $canCloseTraining)
                        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                            <x-button wire:click="triggerSaveDraft" spinner="triggerSaveDraft"
                                class="btn btn-outline btn-primary">
                                <x-icon name="o-document-text" />
                                <span>Save Draft</span>
                            </x-button>
                            <x-button wire:click="triggerCloseTraining" spinner="triggerCloseTraining"
                                class="btn btn-primary">
                                <x-icon name="o-check-circle" />
                                <span>Close Training</span>
                            </x-button>
                        </div>
                    @endif
                @endrole
            </div>
        </x-modal>
    @endif
</div>
