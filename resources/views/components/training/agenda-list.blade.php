<div class="space-y-3">
    @php $items = $this->items; @endphp
    @if ($items->isEmpty())
        <div class="text-center text-gray-500 text-sm py-8">No trainings scheduled this month.</div>
    @else
        @foreach ($items as $item)
            @php
                // Treat item as single training (unique). Compute display date from start_date
                $start = \Carbon\Carbon::parse($item['start_date']);
                $end = \Carbon\Carbon::parse($item['end_date']);
                $rangeText = $start->isSameDay($end)
                    ? $start->format('d M Y')
                    : $start->format('d M') . ' - ' . $end->format('d M Y');
                // Aggregate trainer names (already provided if parent passed trainings)
                $trainerNames = collect($item['trainer_names'] ?? [])
                    ->filter()
                    ->values();
                if ($trainerNames->isEmpty()) {
                    // Fallback derive from sessions array
                    $trainerNames = collect($item['sessions'] ?? [])
                        ->map(
                            fn($s) => is_array($s)
                                ? $s['trainer']['name'] ?? null
                                : $s->trainer->name ?? ($s->trainer->user->name ?? null),
                        )
                        ->filter()
                        ->unique()
                        ->values();
                }
            @endphp
            @php
                $type = strtoupper($item['type'] ?? '');
                $status = strtolower($item['status'] ?? '');
                $isDone = in_array($status, ['done', 'approved', 'rejected']);
                $isFaded = $item['is_faded'] ?? false;
                $colorClasses = match ($type) {
                    'IN' => [
                        'dot' => $isDone || $isFaded ? 'bg-green-300' : 'bg-green-500',
                        'badge' =>
                            $isDone || $isFaded ? 'border-green-300 bg-green-50' : 'border-green-500 bg-green-50',
                    ],
                    'OUT' => [
                        'dot' => $isDone || $isFaded ? 'bg-amber-300' : 'bg-amber-500',
                        'badge' =>
                            $isDone || $isFaded ? 'border-amber-300 bg-amber-50' : 'border-amber-500 bg-amber-50',
                    ],
                    'LMS' => [
                        'dot' => $isDone || $isFaded ? 'bg-indigo-300' : 'bg-indigo-500',
                        'badge' =>
                            $isDone || $isFaded ? 'border-indigo-300 bg-indigo-50' : 'border-indigo-500 bg-indigo-50',
                    ],
                    'BLENDED' => [
                        'dot' => $isDone || $isFaded ? 'bg-purple-300' : 'bg-purple-500',
                        'badge' =>
                            $isDone || $isFaded ? 'border-purple-300 bg-purple-50' : 'border-purple-500 bg-purple-50',
                    ],
                    default => [
                        'dot' => $isDone || $isFaded ? 'bg-primary/60' : 'bg-primary',
                        'badge' =>
                            $isDone || $isFaded ? 'border-primary/50 bg-[#E4F3FF]' : 'border-primary bg-[#E4F3FF]',
                    ],
                };
            @endphp
            @php
                $baseCard =
                    'bg-white border border-gray-200 rounded-lg shadow-sm p-3 flex gap-4 items-start transition';
                // Closed trainings still clickable for details: keep pointer + subtle hover, but keep faded look
                $interactive =
                    $isDone || $isFaded
                        ? 'opacity-60 cursor-pointer hover:border-primary/30'
                        : 'cursor-pointer hover:border-primary/40';
            @endphp
            <div x-on:click="$dispatch('detail-loading-start')" class="{{ $baseCard }} {{ $interactive }}"
                wire:click="open({{ $item['id'] }}, '{{ $item['start_iso'] ?? $start->format('Y-m-d') }}')">
                <div class="flex flex-col items-center w-12 sm:w-14">
                    <div class="text-[10px] sm:text-[11px] uppercase tracking-wide text-gray-500">
                        {{ $start->format('D') }}</div>
                    <div class="text-lg sm:text-2xl font-bold text-gray-800">
                        {{ $start->format('d') }}</div>
                    <div class="text-[10px] text-gray-400">{{ $start->format('M') }}</div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-block w-2 h-2 rounded-full {{ $colorClasses['dot'] }}"></span>
                        <h3 class="font-semibold text-sm sm:text-base leading-snug truncate flex items-center gap-2">
                            @if ($type)
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-white/70 border {{ $colorClasses['badge'] }}">{{ $type }}</span>
                            @endif
                            <span class="truncate">{{ $item['name'] }}</span>
                        </h3>
                    </div>
                    @if ($trainerNames->isNotEmpty())
                        @php $firstTrainer = $trainerNames->first(); @endphp
                        <div class="text-[11px] sm:text-xs text-gray-600 flex items-center gap-1">
                            <span
                                class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-primary/10 text-primary text-[10px] sm:text-[11px] font-semibold">
                                {{ collect(explode(' ', $firstTrainer))->map(fn($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}
                            </span>
                            <span class="truncate max-w-[150px] sm:max-w-[260px]">
                                {{ $trainerNames->take(2)->implode(', ') }}@if ($trainerNames->count() > 2)
                                    , +{{ $trainerNames->count() - 2 }} others
                                @endif
                            </span>
                        </div>
                    @endif
                    <div class="text-[10px] sm:text-[11px] text-gray-500 mt-1">
                        {{ $rangeText }} • {{ $item['day_span'] }} day(s)
                    </div>
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
</div>
