<div class="space-y-3">
    @php $hasAny = collect($days)->some(fn($d) => !empty($d['sessions'])); @endphp
    @if (!$hasAny)
        <div class="text-center text-gray-500 text-sm py-8">No certifications scheduled this month.</div>
    @else
        @foreach ($days as $d)
            @if (!empty($d['sessions']))
                @php $day = $d['date']; @endphp
                @foreach ($d['sessions'] as $s)
                    @php
                        $timeRange = ($s['time']['start'] ?? null) && ($s['time']['end'] ?? null)
                            ? (\Carbon\Carbon::parse($s['time']['start'])->format('H:i') . ' - ' . \Carbon\Carbon::parse($s['time']['end'])->format('H:i'))
                            : null;
                        $type = strtoupper($s['type'] ?? '');
                    @endphp
                    <div class="w-full rounded-xl bg-gradient-to-r from-yellow-300 via-yellow-200 to-yellow-100 px-4 py-3 shadow-sm cursor-pointer hover:opacity-95"
                        wire:click="open({{ $s['id'] }})">
                        <div class="flex items-start gap-4">
                            <div class="flex flex-col items-center w-12 sm:w-14 text-gray-800">
                                <div class="text-[10px] sm:text-[11px] uppercase tracking-wide text-gray-700">{{ $day->format('D') }}</div>
                                <div class="text-lg sm:text-2xl font-bold">{{ $day->format('d') }}</div>
                                <div class="text-[10px] text-gray-700">{{ $day->format('M') }}</div>
                            </div>
                            <div class="flex-1 min-w-0 text-gray-900">
                                <div class="flex items-center gap-2">
                                    @if ($type)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] border border-yellow-500/60 bg-white/40 text-yellow-800">{{ $type }}</span>
                                    @endif
                                    <h3 class="font-semibold text-sm sm:text-base leading-snug truncate flex items-center gap-2">
                                        <span class="truncate">{{ $s['title'] }}</span>
                                    </h3>
                                </div>
                                @if($timeRange)
                                    <span class="text-[10px] sm:text-xs text-gray-700">{{ $timeRange }}</span>
                                @endif
                                @if (!empty($s['location']))
                                    <div class="text-[11px] sm:text-xs text-gray-800">
                                        {{ $s['location'] }}
                                    </div>
                                @endif
                            </div>
                            <div class="self-center text-gray-700/70">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        @endforeach
    @endif
</div>
