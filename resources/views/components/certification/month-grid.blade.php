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
                <div wire:click="addForDate('{{ $isoDate }}')"
                    class="border-b border-r border-gray-200 relative flex flex-col h-20 sm:h-56 bg-white hover:bg-gray-100 transition cursor-pointer group">
                    <div class="flex justify-between items-start p-1 sm:p-2">
                        <span class="font-medium text-[10px] sm:text-sm w-5 h-5 sm:w-7 sm:h-7 flex items-center justify-center rounded-full {{ $day['isToday'] ? 'bg-primary text-white' : ($day['isCurrentMonth'] ? 'text-gray-800' : 'text-gray-400') }}">{{ $day['date']->format('j') }}</span>
                        <button class="opacity-0 group-hover:opacity-100 transition text-gray-400 hover:text-primary" title="Add certification" wire:click.stop="addForDate('{{ $isoDate }}')">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                    @if (!empty($day['sessions']))
                        <div class="flex-1 overflow-y-auto px-1 flex flex-col gap-1">
                            @foreach ($day['sessions'] as $s)
                                @php $type = strtoupper($s['type'] ?? ''); @endphp
                                @php
                                    $start = $s['time']['start'] ?? null; $end = $s['time']['end'] ?? null;
                                    $color = 'border-yellow-500 bg-yellow-50 hover:bg-yellow-100';
                                @endphp
                                <div wire:click.stop="open({{ $s['id'] }})" title="Open details"
                                    class="border-l-4 {{ $color }} rounded-sm sm:rounded-md p-1 sm:py-1.5 sm:px-2 text-[10px] sm:text-xs flex flex-col gap-0.5 shadow-sm cursor-pointer">
                                    <div class="font-semibold leading-tight truncate flex items-center gap-1">
                                        @if ($type)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] bg-white/70 border border-yellow-400 text-yellow-700">{{ $type }}</span>
                                        @endif
                                        <span class="truncate">{{ $s['title'] }}</span>
                                    </div>
                                    @if ($start && $end)
                                        <div class="text-[9px] sm:text-[11px] text-gray-600">{{ \Carbon\Carbon::parse($start)->format('H:i') }} - {{ \Carbon\Carbon::parse($end)->format('H:i') }}</div>
                                    @endif
                                    <div class="text-[9px] sm:text-[11px] text-gray-600 truncate">{{ $s['location'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="border-b border-r border-gray-200 relative flex flex-col h-20 sm:h-56 bg-white">
                    <div class="flex justify-between items-start p-1 sm:p-2">
                        <span class="font-medium text-[10px] sm:text-sm w-5 h-5 sm:w-7 sm:h-7 flex items-center justify-center rounded-full {{ $day['isToday'] ? 'bg-primary text-white' : ($day['isCurrentMonth'] ? 'text-gray-800' : 'text-gray-400') }}">{{ $day['date']->format('j') }}</span>
                    </div>
                    @if (!empty($day['sessions']))
                        <div class="flex-1 overflow-y-auto px-1 flex flex-col gap-1">
                            @foreach ($day['sessions'] as $s)
                                @php $type = strtoupper($s['type'] ?? ''); $start = $s['time']['start'] ?? null; $end = $s['time']['end'] ?? null; @endphp
                                <div wire:click="open({{ $s['id'] }})" title="Open details"
                                    class="border-l-4 border-yellow-500 bg-yellow-50 rounded-sm sm:rounded-md p-1 sm:py-1.5 sm:px-2 text-[10px] sm:text-xs flex flex-col gap-0.5 shadow-sm cursor-pointer hover:bg-yellow-100">
                                    <div class="font-semibold leading-tight truncate flex items-center gap-1">
                                        @if ($type)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] bg-white/70 border border-yellow-400 text-yellow-700">{{ $type }}</span>
                                        @endif
                                        <span class="truncate">{{ $s['title'] }}</span>
                                    </div>
                                    @if ($start && $end)
                                        <div class="text-[9px] sm:text-[11px] text-gray-600">{{ \Carbon\Carbon::parse($start)->format('H:i') }} - {{ \Carbon\Carbon::parse($end)->format('H:i') }}</div>
                                    @endif
                                    <div class="text-[9px] sm:text-[11px] text-gray-600 truncate">{{ $s['location'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endrole
        @endforeach
    </div>
</div>
