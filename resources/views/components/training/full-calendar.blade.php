<div class="bg-white rounded-lg overflow-hidden border border-gray-200 shadow">
    <div class="grid grid-cols-7 bg-gradient-to-r from-[#4863a0] via-[#123456] to-[#4863a0]">
        @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $dayLabel)
            <div class="p-1 sm:p-3 text-center text-white font-medium text-[10px] sm:text-sm tracking-wide">
                {{ $dayLabel }}</div>
        @endforeach
    </div>
    <div class="grid grid-cols-7">
        @foreach ($days as $day)
            @php $isoDate = $day['date']->format('Y-m-d'); @endphp
            @role('admin')
                <div wire:click="openAdd('{{ $isoDate }}')"
                    class="border-b border-r border-gray-200 relative flex flex-col h-20 sm:h-56 bg-white hover:bg-gray-100 transition cursor-pointer group">
                    <div class="flex justify-between items-start p-1 sm:p-2">
                        <span
                            class="font-medium text-[10px] sm:text-sm w-5 h-5 sm:w-7 sm:h-7 flex items-center justify-center rounded-full {{ $day['isToday'] ? 'bg-primary text-white' : ($day['isCurrentMonth'] ? 'text-gray-800' : 'text-gray-400') }}">{{ $day['date']->format('j') }}</span>
                        <button class="opacity-0 group-hover:opacity-100 transition text-gray-400 hover:text-primary"
                            title="Add training" wire:click.stop="openAdd('{{ $isoDate }}')">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                    @if (!empty($day['trainings']))
                        <div class="flex-1 overflow-y-auto px-1 flex flex-col gap-1">
                            @foreach ($day['trainings'] as $training)
                                @php
                                    // Match session by exact date (iso_date) to ensure correct trainer for each day; fallback to earliest session
                                    $sessionForDay =
                                        $training['sessions']->firstWhere('iso_date', $isoDate) ??
                                        $training['sessions']->sortBy('day_number')->first();
                                @endphp
                                @php
                                    $type = strtoupper($training['type'] ?? '');
                                    $isDone = strtolower($training['status'] ?? '') === 'done';
                                    switch ($type) {
                                        case 'IN':
                                            $typeColor = $isDone
                                                ? 'border-green-300 bg-green-50 hover:bg-green-100 opacity-70'
                                                : 'border-green-500 bg-green-50 hover:bg-green-100';
                                            break;
                                        case 'OUT':
                                            $typeColor = $isDone
                                                ? 'border-amber-300 bg-amber-50 hover:bg-amber-100 opacity-70'
                                                : 'border-amber-500 bg-amber-50 hover:bg-amber-100';
                                            break;
                                        case 'LMS':
                                            $typeColor = $isDone
                                                ? 'border-indigo-300 bg-indigo-50 hover:bg-indigo-100 opacity-70'
                                                : 'border-indigo-500 bg-indigo-50 hover:bg-indigo-100';
                                            break;
                                        default:
                                            $typeColor = $isDone
                                                ? 'border-primary/50 bg-[#E4F3FF] hover:bg-[#d4ebfc] opacity-70'
                                                : 'border-primary bg-[#E4F3FF] hover:bg-[#d4ebfc]';
                                            break;
                                    }
                                    $typeLabel = $type ?: '';
                                @endphp
                                <div x-on:click="$dispatch('detail-loading-start')"
                                    wire:click.stop="openTraining({{ $training['id'] }}, '{{ $isoDate }}')"
                                    class="{{ $typeColor }} border-l-4 rounded-sm sm:rounded-md p-1 sm:py-1.5 sm:px-2 text-[10px] sm:text-xs flex flex-col gap-0.5 shadow-sm cursor-pointer">
                                    <div class="font-semibold leading-tight truncate flex items-center gap-1">
                                        @if ($typeLabel)
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] bg-white/70 border {{ $typeColor }}">
                                                {{ $typeLabel }}
                                            </span>
                                        @endif
                                        <span class="truncate">{{ $training['name'] }}</span>
                                    </div>
                                    @if ($sessionForDay && ($sessionForDay->trainer || $sessionForDay->trainer_display_name))
                                        @php $trainerName = $sessionForDay->trainer_display_name ?? ($sessionForDay->trainer->name ?? ($sessionForDay->trainer->user->name ?? '')); @endphp
                                        <div class="text-[9px] sm:text-[11px] text-gray-600 flex items-center gap-1">
                                            <span
                                                class="inline-block bg-primary/10 text-primary px-1.5 py-0.5 rounded-full">
                                                {{ $sessionForDay->trainer_initials ?? Str::of($trainerName)->explode(' ')->map(fn($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}
                                            </span>
                                            <span class="truncate max-w-[70px] sm:max-w-[120px]">{{ $trainerName }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="border-b border-r border-gray-200 relative flex flex-col h-20 sm:h-56 bg-white">
                    <div class="flex justify-between items-start p-1 sm:p-2">
                        <span
                            class="font-medium text-[10px] sm:text-sm w-5 h-5 sm:w-7 sm:h-7 flex items-center justify-center rounded-full {{ $day['isToday'] ? 'bg-primary text-white' : ($day['isCurrentMonth'] ? 'text-gray-800' : 'text-gray-400') }}">{{ $day['date']->format('j') }}</span>
                    </div>
                    @if (!empty($day['trainings']))
                        <div class="flex-1 overflow-y-auto px-1 flex flex-col gap-1">
                            @foreach ($day['trainings'] as $training)
                                @php
                                    // Match session by exact date (iso_date) to ensure correct trainer for each day; fallback to earliest session
                                    $sessionForDay =
                                        $training['sessions']->firstWhere('iso_date', $isoDate) ??
                                        $training['sessions']->sortBy('day_number')->first();
                                @endphp
                                @php
                                    $type = strtoupper($training['type'] ?? '');
                                    $isDone = strtolower($training['status'] ?? '') === 'done';
                                    switch ($type) {
                                        case 'IN':
                                            $typeColor = $isDone
                                                ? 'border-green-300 bg-green-50 hover:bg-green-100 opacity-70'
                                                : 'border-green-500 bg-green-50 hover:bg-green-100';
                                            break;
                                        case 'OUT':
                                            $typeColor = $isDone
                                                ? 'border-amber-300 bg-amber-50 hover:bg-amber-100 opacity-70'
                                                : 'border-amber-500 bg-amber-50 hover:bg-amber-100';
                                            break;
                                        case 'LMS':
                                            $typeColor = $isDone
                                                ? 'border-indigo-300 bg-indigo-50 hover:bg-indigo-100 opacity-70'
                                                : 'border-indigo-500 bg-indigo-50 hover:bg-indigo-100';
                                            break;
                                        default:
                                            $typeColor = $isDone
                                                ? 'border-primary/50 bg-[#E4F3FF] hover:bg-[#d4ebfc] opacity-70'
                                                : 'border-primary bg-[#E4F3FF] hover:bg-[#d4ebfc]';
                                            break;
                                    }
                                    $typeLabel = $type ?: '';
                                @endphp
                                <div x-on:click="$dispatch('detail-loading-start')"
                                    wire:click.stop="openTraining({{ $training['id'] }}, '{{ $isoDate }}')"
                                    class="{{ $typeColor }} border-l-4 rounded-sm sm:rounded-md p-1 sm:py-1.5 sm:px-2 text-[10px] sm:text-xs flex flex-col gap-0.5 shadow-sm cursor-pointer">
                                    <div class="font-semibold leading-tight truncate flex items-center gap-1">
                                        @if ($typeLabel)
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] bg-white/70 border {{ $typeColor }}">
                                                {{ $typeLabel }}
                                            </span>
                                        @endif
                                        <span class="truncate">{{ $training['name'] }}</span>
                                    </div>
                                    @if ($sessionForDay && ($sessionForDay->trainer || $sessionForDay->trainer_display_name))
                                        @php $trainerName = $sessionForDay->trainer_display_name ?? ($sessionForDay->trainer->name ?? ($sessionForDay->trainer->user->name ?? '')); @endphp
                                        <div class="text-[9px] sm:text-[11px] text-gray-600 flex items-center gap-1">
                                            <span
                                                class="inline-block bg-primary/10 text-primary px-1.5 py-0.5 rounded-full">
                                                {{ $sessionForDay->trainer_initials ?? Str::of($trainerName)->explode(' ')->map(fn($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}
                                            </span>
                                            <span class="truncate max-w-[70px] sm:max-w-[120px]">{{ $trainerName }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endrole
        @endforeach
    </div>
</div>
