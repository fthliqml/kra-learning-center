<div class="bg-white">
    <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start">
        Training Schedule
    </h1>

    <!-- Month Navigation -->
    <div class="flex items-center justify-center mb-4">
        <button wire:click="previousMonth" class="p-2 hover:bg-gray-100 rounded-full cursor-pointer">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
        <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mx-4">{{ $monthName }}</h2>
        <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 rounded-full cursor-pointer">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-lg overflow-hidden border border-gray-200 shadow">
        <!-- Day Headers -->
        <div class="grid grid-cols-7" style="background: linear-gradient(to right, #4863a0, #123456, #4863a0)">
            <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">MON
            </div>
            <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">TUE
            </div>
            <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">WED
            </div>
            <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">THU
            </div>
            <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">FRI
            </div>
            <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">SAT
            </div>
            <div class="p-1 sm:p-4 text-center text-white font-medium text-xs sm:text-base">SUN</div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7">
            @foreach ($days as $day)
                <div @class([
                    'border-b border-gray-200 relative flex flex-col border-r',
                    'h-[65px] sm:h-[130px]' => $hasScrollableDay,
                    'h-[60px] sm:h-[120px]' => !$hasScrollableDay,
                    'bg-gray-50' => !$day['isCurrentMonth'],
                    'bg-white' => $day['isCurrentMonth'],
                ])>
                    <!-- Date Number -->
                    <div class="flex justify-between items-start p-0.5 sm:p-2">
                        <span
                            class="font-medium text-[8px] sm:text-base w-3 h-3 sm:w-8 sm:h-8 {{ $day['isToday'] ? 'bg-primary text-white rounded-full flex items-center justify-center' : ($day['isCurrentMonth'] ? 'text-gray-900' : 'text-gray-400') }}">
                            {{ $day['date']->format('j') }}
                        </span>
                    </div>

                    <!-- Multiple Training Events dengan layering system -->
                    @if (!empty($day['trainings']))
                        <div @class([
                            'px-0.5 sm:px-1 mb-0.5 sm:mb-2 flex-1 flex flex-col gap-0.5 sm:gap-1',
                            'overflow-y-auto' => count($day['trainings']) > 5,
                        ])>
                            @foreach ($day['trainings'] as $training)
                                <div wire:click="openEventModal({{ $training['id'] }})" @class([
                                    'bg-[#C0E4FF] hover:bg-[#A8D5FF] cursor-pointer transition-colors duration-200',
                                    'py-0.5 px-0.5 text-[7px] h-2.5 sm:py-1 sm:px-2 sm:text-xs sm:h-6 flex-shrink-0',
                                    'border-l-[1px] sm:border-l-4 border-tetriary rounded-sm sm:rounded-md',
                                ])>
                                    <!-- Hanya tampilkan nama training, dan hanya di hari start -->
                                    <div class="font-medium text-black leading-2 sm:leading-4 truncate">
                                        {{ $training['title'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>


    @isset($selectedEvent)
        <!-- Modal for Event Details -->
        <x-modal wire:model="modal" icon='o-document-text' title="Training Details"
            subtitle="Information about training details" separator box-class="max-w-4xl h-fit">
            <div class="grid grid-cols-2 gap-5 justify-items-center items-center border-b border-gray-400 pb-5">
                <x-card body-class="flex justify-center items-center gap-5"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#FF6B6B]" separator>
                    <x-icon name="o-document-text" class="w-7 h-7 text-[#FF6B6B]" />
                    <div>
                        <span class="text-sm text-gray-700">Training Name</span>
                        <h1 class="font-semibold">{{ $selectedEvent['title'] ?? false }}</h1>
                    </div>
                </x-card>
                <x-card body-class="flex justify-center items-center gap-5"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-primary" separator>
                    <x-icon name="o-calendar-date-range" class="w-7 h-7 text-primary" />
                    <div>
                        <span class="text-sm text-gray-700">Training Date</span>
                        <h1 class="font-semibold">
                            {{ formatRangeDate($selectedEvent['start_date'], $selectedEvent['end_date']) }}</h1>
                    </div>
                </x-card>

                <x-card body-class="flex justify-center items-center gap-5"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#6A4C93]" separator>
                    <x-icon name="o-document-text" class="w-7 h-7 text-[#6A4C93]" />
                    <div>
                        <span class="text-sm text-gray-700">Instructor</span>
                        <h1 class="font-semibold">{{ $selectedEvent['instructor'] ?? false }}</h1>
                    </div>
                </x-card>

                <x-card body-class="flex justify-center items-center gap-5"
                    class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#4CAF50]" separator>
                    <x-icon name="o-calendar-date-range" class="w-7 h-7 text-[#4CAF50]" />
                    <div>
                        <span class="text-sm text-gray-700">Location</span>
                        <h1 class="font-semibold">
                            {{ $selectedEvent['location'] }}</h1>
                    </div>
                </x-card>
            </div>

            <div class="mt-5">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-xl font-bold ">Attendance List</h2>
                    <div class="flex gap-2">
                        <x-ui.button variant="primary"> Update <x-icon name="o-pencil-square" class="w-5 h-5" />
                        </x-ui.button>
                        <x-select placeholder="06 May 2025" :options="[
                            ['id' => '07 May 2025', 'name' => '07 May 2025'],
                            ['id' => '08 May 2025', 'name' => '08 May 2025'],
                        ]" />

                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
                    <x-table :headers="[
                        ['key' => 'no', 'label' => 'No', 'class' => 'text-center'],
                        ['key' => 'nrp', 'label' => 'NRP', 'class' => 'text-center'],
                        ['key' => 'name', 'label' => 'Name', 'class' => 'w-[100px] text-center'],
                        ['key' => 'section', 'label' => 'Section'],
                        ['key' => 'attendance', 'label' => 'Attendance', 'class' => 'text-center'],
                        ['key' => 'remark', 'label' => 'Remark', 'class' => 'text-center'],
                    ]" :rows="$rows" striped>

                        {{-- Custom cell No --}}
                        @scope('cell_no', $row)
                            {{ $loop->iteration }}
                        @endscope

                        {{-- Custom cell Attendance --}}
                        @scope('cell_attendance', $row)
                            <x-select placeholder="Select" :options="[
                                ['id' => 'present', 'name' => 'Present'],
                                ['id' => 'absent', 'name' => 'Absent'],
                            ]" class="!w-28 !h-8 text-sm" />
                        @endscope

                        {{-- Custom cell Remark --}}
                        @scope('cell_remark', $row)
                            <x-input placeholder="" class="!w-40 !h-8 text-sm" />
                        @endscope

                    </x-table>

                </div>
            </div>
        </x-modal>
    @endisset

</div>
