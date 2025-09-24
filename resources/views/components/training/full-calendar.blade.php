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
            <div wire:click="openAdd('{{ $isoDate }}')"
                class="border-b border-r border-gray-200 relative flex flex-col h-20 sm:h-60 bg-white hover:bg-gray-100 transition cursor-pointer group">
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
                            <div x-on:click="$dispatch('detail-loading-start')"
                                wire:click.stop="openTraining({{ $training['id'] }}, '{{ $isoDate }}')"
                                class="bg-[#E4F3FF] hover:bg-[#d4ebfc] border-l-4 border-primary rounded-sm sm:rounded-md p-1 sm:py-1.5 sm:px-2 text-[10px] sm:text-xs flex flex-col gap-0.5 shadow-sm">
                                <div class="font-semibold leading-tight truncate">{{ $training['name'] }}</div>
                                @php $firstSession = $training['sessions']->first(); @endphp
                                @if ($firstSession && $firstSession->trainer)
                                    <div class="text-[9px] sm:text-[11px] text-gray-600 flex items-center gap-1">
                                        <span
                                            class="inline-block bg-primary/10 text-primary px-1.5 py-0.5 rounded-full">{{ Str::of($firstSession->trainer->name ?? ($firstSession->trainer->user->name ?? ''))->explode(' ')->map(fn($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}</span>
                                        <span
                                            class="truncate max-w-[70px] sm:max-w-[120px]">{{ $firstSession->trainer->name ?? ($firstSession->trainer->user->name ?? '') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
