<div class="space-y-3">
    @php $items = $this->items; @endphp
    @if ($items->isEmpty())
        <div class="text-center text-gray-500 text-sm py-8">No trainings scheduled this month.</div>
    @else
        @foreach ($items as $item)
            @php
                // Pick the session that matches this agenda item's date; fallback to the earliest by day_number
$sessionForDay =
    $item['sessions']->firstWhere('iso_date', $item['iso']) ??
    $item['sessions']->sortBy('day_number')->first();
            @endphp
            <div x-on:click="$dispatch('detail-loading-start')"
                class="bg-white border border-gray-200 rounded-lg shadow-sm p-3 flex gap-4 items-start hover:border-primary/40 transition cursor-pointer"
                wire:click="open({{ $item['id'] }}, '{{ $item['iso'] }}')">
                <div class="flex flex-col items-center w-12 sm:w-14">
                    <div class="text-[10px] sm:text-[11px] uppercase tracking-wide text-gray-500">
                        {{ $item['date']->format('D') }}</div>
                    <div class="text-lg sm:text-2xl font-bold {{ $item['isToday'] ? 'text-primary' : 'text-gray-800' }}">
                        {{ $item['date']->format('d') }}</div>
                    <div class="text-[10px] text-gray-400">{{ $item['date']->format('M') }}</div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-primary"></span>
                        <h3 class="font-semibold text-sm sm:text-base leading-snug truncate">{{ $item['name'] }}</h3>
                    </div>
                    @if ($sessionForDay && ($sessionForDay->trainer || $sessionForDay->trainer_display_name))
                        @php $trainerName = $sessionForDay->trainer_display_name ?? ($sessionForDay->trainer->name ?? ($sessionForDay->trainer->user->name ?? '')); @endphp
                        <div class="text-[11px] sm:text-xs text-gray-600 flex items-center gap-1">
                            <span
                                class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-primary/10 text-primary text-[10px] sm:text-[11px] font-semibold">
                                {{ $sessionForDay->trainer_initials ?? Str::of($trainerName)->explode(' ')->map(fn($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}
                            </span>
                            <span class="truncate max-w-[150px] sm:max-w-[260px]">{{ $trainerName }}</span>
                        </div>
                    @endif
                    @if ($sessionForDay)
                        <div class="text-[10px] sm:text-[11px] text-gray-500 mt-1">
                            Day {{ $sessionForDay->day_number ?? '-' }} • {{ $sessionForDay->room_name ?? '-' }}
                            {{ $sessionForDay->room_location ? '(' . $sessionForDay->room_location . ')' : '' }}
                        </div>
                    @endif
                </div>
                <div class="self-center text-gray-400">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>
        @endforeach
    @endif
</div>
