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
            @php $isAdmin = auth()->user()?->hasRole('admin') ?? false; @endphp
            
            <div 
                @if($isAdmin) wire:click="openAdd('{{ $isoDate }}')" @endif
                class="border-b border-r border-gray-200 relative flex flex-col h-20 sm:h-56 bg-white {{ $isAdmin ? 'hover:bg-gray-100 transition cursor-pointer' : '' }} group">
                <div class="flex justify-between items-start p-1 sm:p-2">
                    <span
                        class="font-medium text-[10px] sm:text-sm w-5 h-5 sm:w-7 sm:h-7 flex items-center justify-center rounded-full {{ $day['isToday'] ? 'bg-primary text-white' : ($day['isCurrentMonth'] ? 'text-gray-800' : 'text-gray-400') }}">{{ $day['date']->format('j') }}</span>
                    @if($isAdmin)
                        <button class="opacity-0 group-hover:opacity-100 transition text-gray-400 hover:text-primary"
                            title="Add training" wire:click.stop="openAdd('{{ $isoDate }}')">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    @endif
                </div>
                @if (!empty($day['trainings']))
                    <div class="flex-1 overflow-y-auto px-1 flex flex-col gap-1">
                        @foreach ($day['trainings'] as $training)
                            @php
                                // Match session by exact date (iso_date) to ensure correct trainer for each day; fallback to earliest session
                                $sessionForDay =
                                    $training['sessions']->firstWhere('iso_date', $isoDate) ??
                                    $training['sessions']->sortBy('day_number')->first();
                                
                                $type = strtoupper($training['type'] ?? '');
                                $status = strtolower($training['status'] ?? '');
                                $isDone = in_array($status, ['done', 'approved', 'rejected']);
                                $isFaded = $training['is_faded'] ?? false;
                                
                                // Determine color based on type
                                $typeColor = match($type) {
                                    'IN' => ($isDone || $isFaded) ? 'border-green-300 bg-green-50 hover:bg-green-100 opacity-60'
                                        : 'border-green-500 bg-green-50 hover:bg-green-100',
                                    'OUT' => ($isDone || $isFaded) ? 'border-amber-300 bg-amber-50 hover:bg-amber-100 opacity-60'
                                        : 'border-amber-500 bg-amber-50 hover:bg-amber-100',
                                    'LMS' => ($isDone || $isFaded) ? 'border-indigo-300 bg-indigo-50 hover:bg-indigo-100 opacity-60'
                                        : 'border-indigo-500 bg-indigo-50 hover:bg-indigo-100',
                                    'BLENDED' => ($isDone || $isFaded) ? 'border-purple-300 bg-purple-50 hover:bg-purple-100 opacity-60'
                                        : 'border-purple-500 bg-purple-50 hover:bg-purple-100',
                                    default => ($isDone || $isFaded) ? 'border-primary/50 bg-[#E4F3FF] hover:bg-[#d4ebfc] opacity-60'
                                        : 'border-primary bg-[#E4F3FF] hover:bg-[#d4ebfc]',
                                };
                                $typeLabel = $type ?: '';
                            @endphp
                            @include('components.training.partials.calendar-training-card', [
                                'training' => $training,
                                'isoDate' => $isoDate,
                                'sessionForDay' => $sessionForDay,
                                'typeColor' => $typeColor,
                                'typeLabel' => $typeLabel,
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
