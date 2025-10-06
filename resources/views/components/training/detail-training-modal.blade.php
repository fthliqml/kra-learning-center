<div>
    @if ($selectedEvent)
        <x-modal wire:model="modal" icon='o-document-text' title="Training Details"
            subtitle="Information about training details" separator
            box-class="w-[calc(100vw-2rem)] max-w-full sm:max-w-4xl h-fit">
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 justify-items-stretch items-stretch border-b border-gray-400 pb-5">
                {{-- Training Name Card --}}
                <x-card body-class="flex justify-center items-center gap-5 relative group"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#FF6B6B] hover:bg-gray-100 cursor-pointer transition-colors"
                    wire:click="$toggle('editModes.training_name')" separator>
                    <x-icon name="o-document-text" class="w-7 h-7 text-[#FF6B6B]" />
                    <div class="flex-1">
                        <span class="text-sm text-gray-700">Training Name</span>
                        @if ($editModes['training_name'])
                            <x-input type="text" wire:model="selectedEvent.name" wire:click.stop
                                class="rounded-none border-t-0 border-x-0 border-b-primary focus:outline-none focus-within:outline-none px-0 bg-transparent w-full mr-10" />
                        @else
                            <h1 class="font-semibold cursor-pointer">{{ $selectedEvent['name'] ?? '-' }}</h1>
                        @endif
                    </div>
                    <x-icon name="{{ $editModes['training_name'] ? 'o-check' : 'o-pencil' }}"
                        class="w-5 h-5 text-gray-400 absolute top-3.5 right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        wire:click="$toggle('editModes.training_name')" />
                </x-card>

                {{-- Date Range Card --}}
                <x-card body-class="flex justify-center items-center gap-5 relative group"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-primary hover:bg-gray-100 cursor-pointer transition-colors"
                    wire:click="$toggle('editModes.date')" separator>
                    <x-icon name="o-calendar-date-range" class="w-7 h-7 text-primary" />
                    <div class="flex-1">
                        <span class="text-sm text-gray-700">Training Date</span>
                        @if ($editModes['date'])
                            <div onclick="event.stopPropagation()">
                                <x-datepicker wire:model.defer="trainingDateRange" placeholder="Select date range"
                                    icon="o-calendar" :config="$config = [
                                        'mode' => 'range',
                                        'altInput' => true,
                                        'altFormat' => 'd M Y',
                                    ]" class="w-full bg-transparent" />
                            </div>
                        @else
                            <h1 class="font-semibold">
                                {{ formatRangeDate($selectedEvent['start_date'], $selectedEvent['end_date']) }}</h1>
                        @endif
                    </div>
                    <x-icon name="{{ $editModes['date'] ? 'o-check' : 'o-pencil' }}"
                        class="w-5 h-5 text-gray-400 absolute {{ $editModes['date'] ? 'top-0' : 'top-3.5' }} right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        wire:click="$toggle('editModes.date')" />
                </x-card>

                {{-- Trainer Card --}}
                <x-card body-class="flex justify-center items-center gap-5 relative group"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#6A4C93] hover:bg-gray-100 cursor-pointer"
                    wire:click="$toggle('editModes.trainer')" separator>
                    <x-icon name="o-user" class="w-7 h-7 text-[#6A4C93]" />
                    <div class="flex-1">
                        <span class="text-sm text-gray-700">Instructor</span>
                        @if ($editModes['trainer'])
                            <div onclick="event.stopPropagation()">
                                <x-select wire:model="trainer.id" :options="$trainers" option-label="name"
                                    option-value="id" class="w-full focus-within:outline-none" />
                            </div>
                        @else
                            <h1 class="font-semibold">{{ $trainer['name'] ?? '-' }}</h1>
                        @endif
                    </div>
                    <x-icon name="{{ $editModes['trainer'] ? 'o-check' : 'o-pencil' }}"
                        class="w-5 h-5 text-gray-400 absolute {{ $editModes['trainer'] ? 'top-0' : 'top-3.5' }} right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        wire:click="$toggle('editModes.trainer')" />
                </x-card>

                {{-- Location Card --}}
                <x-card body-class="flex justify-center items-center gap-5 relative group"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#4CAF50] hover:bg-gray-100 cursor-pointer transition-colors"
                    wire:click="$toggle('editModes.location')" separator>
                    <x-icon name="o-map-pin" class="w-7 h-7 text-[#4CAF50]" />
                    <div class="flex-1">
                        <span class="text-sm text-gray-700">Location</span>
                        @if ($editModes['location'])
                            <div class="flex gap-5">
                                <x-input type="text" wire:model="sessions.{{ $dayNumber - 1 }}.room_name"
                                    wire:click.stop label="Name"
                                    class="rounded-none border-t-0 border-x-0 border-b-primary focus:outline-none focus-within:outline-none w-full bg-transparent px-0" />
                                <x-input type="text" wire:model="sessions.{{ $dayNumber - 1 }}.room_location"
                                    wire:click.stop label="Office"
                                    class="rounded-none border-t-0 border-x-0 border-b-primary focus:outline-none focus-within:outline-none w-full bg-transparent px-0" />
                            </div>
                        @else
                            <h1 class="font-semibold">{{ $this->currentSession['room_name'] ?? '' }} -
                                {{ $this->currentSession['room_location'] ?? '' }}</h1>
                        @endif
                    </div>
                    <x-icon name="{{ $editModes['location'] ? 'o-check' : 'o-pencil' }}"
                        class="w-5 h-5 text-gray-400 absolute top-3.5 right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        wire:click="$toggle('editModes.location')" />
                </x-card>
                {{-- Group Competency Card (editable) --}}
                <x-card body-class="flex justify-center items-center gap-5 relative group"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#2196F3] hover:bg-gray-100 cursor-pointer"
                    wire:click="$toggle('editModes.group_comp')" separator>
                    <x-icon name="o-rectangle-group" class="w-7 h-7 text-[#2196F3]" />
                    <div class="flex-1">
                        <span class="text-sm text-gray-700">Group Competency</span>
                        @if ($editModes['group_comp'])
                            <div onclick="event.stopPropagation()">
                                <x-select wire:model="selectedEvent.group_comp" :options="[
                                    ['id' => 'BMC', 'name' => 'BMC'],
                                    ['id' => 'BC', 'name' => 'BC'],
                                    ['id' => 'MMP', 'name' => 'MMP'],
                                    ['id' => 'LC', 'name' => 'LC'],
                                    ['id' => 'MDP', 'name' => 'MDP'],
                                    ['id' => 'TOC', 'name' => 'TOC'],
                                ]" option-label="name"
                                    option-value="id" class="w-full focus-within:outline-none" />
                            </div>
                        @else
                            <h1 class="font-semibold">{{ $selectedEvent['group_comp'] ?? '-' }}</h1>
                        @endif
                    </div>
                    <x-icon name="{{ $editModes['group_comp'] ? 'o-check' : 'o-pencil' }}"
                        class="w-5 h-5 text-gray-400 absolute {{ $editModes['group_comp'] ? 'top-0' : 'top-3.5' }} right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        wire:click="$toggle('editModes.group_comp')" />
                </x-card>

                {{-- Time Card --}}
                <x-card body-class="flex justify-center items-center gap-5 relative group"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#FF9800] hover:bg-gray-100 cursor-pointer transition-colors"
                    wire:click="$toggle('editModes.time')" separator>
                    <x-icon name="o-clock" class="w-7 h-7 text-[#FF9800]" />
                    <div class="flex-1">
                        <span class="text-sm text-gray-700">Time</span>
                        @php
                            $st = $this->currentSession['start_time'] ?? null;
                            $et = $this->currentSession['end_time'] ?? null;
                            $st = $st ? substr($st, 0, 5) : null;
                            $et = $et ? substr($et, 0, 5) : null;
                        @endphp
                        @if ($editModes['time'])
                            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                                <x-input type="time" wire:model="sessions.{{ $dayNumber - 1 }}.start_time"
                                    class="!w-24 !h-8 text-sm" />
                                <span>-</span>
                                <x-input type="time" wire:model="sessions.{{ $dayNumber - 1 }}.end_time"
                                    class="!w-24 !h-8 text-sm" />
                            </div>
                        @else
                            <h1 class="font-semibold">{{ $st && $et ? $st . ' - ' . $et : '-' }}</h1>
                        @endif
                    </div>
                    <x-icon name="{{ $editModes['time'] ? 'o-check' : 'o-pencil' }}"
                        class="w-5 h-5 text-gray-400 absolute {{ $editModes['time'] ? 'top-0' : 'top-3.5' }} right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        wire:click="$toggle('editModes.time')" />
                </x-card>
            </div>

            {{-- Attendance Section --}}
            <div class="mt-5">
                <div
                    class="flex justify-between flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 mb-5 w-full">
                    <h2 class="text-xl font-bold">Attendance List</h2>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
                        <x-select wire:model="dayNumber" :options="$trainingDates" option-label="name" option-value="id"
                            wire:change="$refresh" class="w-full sm:w-auto" />
                    </div>
                </div>
                <div
                    class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto overflow-y-auto max-h-[230px]">
                    <div class="min-w-[720px]">
                        <x-table :headers="[
                            ['key' => 'no', 'label' => 'No', 'class' => 'text-center'],
                            ['key' => 'NRP', 'label' => 'NRP', 'class' => 'text-center'],
                            ['key' => 'name', 'label' => 'Name', 'class' => 'w-[250px] text-center'],
                            ['key' => 'section', 'label' => 'Section', 'class' => 'text-center'],
                            ['key' => 'attendance', 'label' => 'Attendance', 'class' => 'text-center'],
                            ['key' => 'remark', 'label' => 'Remark', 'class' => 'text-center'],
                        ]" :rows="$employees" striped>
                            @scope('cell_no', $row)
                                {{ $loop->iteration }}
                            @endscope
                            @scope('cell_attendance', $row)
                                <x-select wire:model="attendances.{{ $this->dayNumber }}.{{ $row->id }}.status"
                                    :options="[
                                        ['id' => 'present', 'name' => 'Present'],
                                        ['id' => 'absent', 'name' => 'Absent'],
                                        ['id' => 'pending', 'name' => 'Pending'],
                                    ]" class="!w-28 !h-8 text-sm" />
                            @endscope
                            @scope('cell_remark', $row)
                                <x-input wire:model="attendances.{{ $this->dayNumber }}.{{ $row->id }}.remark"
                                    placeholder="" class="!w-40 !h-8 text-sm" />
                            @endscope
                        </x-table>
                    </div>
                </div>
                <div
                    class="flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3 mt-5">
                    <x-button wire:click="closeModal"
                        class="btn btn-error bg-white hover:bg-red-100 hover:opacity-80 w-full sm:w-auto">Cancel</x-button>
                    <div class="flex items-center sm:items-center justify-center gap-3 w-full sm:w-auto">
                        <x-button wire:click="requestDeleteConfirm" class="btn-error w-fit sm:w-auto"
                            spinner="requestDeleteConfirm"><x-icon name="o-trash" /><span
                                class="">Delete</span></x-button>
                        <x-button wire:click="update" class="btn btn-success w-fit sm:w-auto"
                            spinner="update"><x-icon name="o-bookmark" /><span class="">Save</span></x-button>
                    </div>
                </div>
                <div class="mt-4 text-xs text-gray-500">Deleting will remove all days, sessions, and attendances.
                </div>
            </div>
        </x-modal>
    @endif
</div>
