<div class="{{ $typeColor }} border-l-4 rounded-sm sm:rounded-md p-1 sm:py-1.5 sm:px-2 text-[10px] sm:text-xs flex flex-col gap-0.5 shadow-sm cursor-pointer"
    x-on:click="$dispatch('detail-loading-start')"
    wire:click.stop="openTraining({{ $training['id'] }}, '{{ $isoDate }}')">
    <div class="font-semibold leading-tight truncate flex items-center gap-1">
        @if ($typeLabel)
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] bg-white/70 border {{ $typeColor }}">
                {{ $typeLabel }}
            </span>
        @endif
        <span class="truncate">{{ $training['name'] }}</span>
    </div>
    @if ($sessionForDay && ($sessionForDay->trainer || $sessionForDay->trainer_display_name))
        @php 
            $trainerName = $sessionForDay->trainer_display_name 
                ?? ($sessionForDay->trainer->name ?? ($sessionForDay->trainer->user->name ?? '')); 
        @endphp
        <div class="text-[9px] sm:text-[11px] text-gray-600 flex items-center gap-1">
            <span class="inline-block bg-primary/10 text-primary px-1.5 py-0.5 rounded-full">
                {{ $sessionForDay->trainer_initials ?? Str::of($trainerName)->explode(' ')->map(fn($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}
            </span>
            <span class="truncate max-w-[70px] sm:max-w-[120px]">{{ $trainerName }}</span>
        </div>
    @endif
</div>
