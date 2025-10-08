@php
    $weeks = 6;
    $daysPerWeek = 7;
@endphp
<div class="bg-white rounded-lg overflow-hidden border border-gray-200 shadow animate-pulse select-none" aria-busy="true">
    <div class="grid grid-cols-7 bg-gradient-to-r from-[#4863a0] via-[#123456] to-[#4863a0]">
        @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $lbl)
            <div class="p-1 sm:p-3 text-center text-white font-medium text-[10px] sm:text-sm tracking-wide">
                {{ $lbl }}</div>
        @endforeach
    </div>
    <div class="grid grid-cols-7">
        @for ($w = 0; $w < $weeks; $w++)
            @for ($d = 0; $d < $daysPerWeek; $d++)
                <div class="border-b border-r border-gray-200 relative flex flex-col h-20 sm:h-56 bg-white">
                    <div class="flex justify-between items-start p-1 sm:p-2">
                        <span class="h-5 w-5 sm:h-7 sm:w-7 rounded-full bg-gray-200"></span>
                        <span class="h-3 w-3 bg-gray-100 rounded-full"></span>
                    </div>
                    <div class="flex-1 overflow-hidden px-1 flex flex-col gap-1">
                        <div class="h-4 sm:h-5 w-full bg-gray-100 rounded"></div>
                        <div class="h-3 sm:h-4 w-2/3 bg-gray-100 rounded"></div>
                        <div class="h-4 sm:h-5 w-5/6 bg-gray-100 rounded"></div>
                    </div>
                </div>
            @endfor
        @endfor
    </div>
</div>
