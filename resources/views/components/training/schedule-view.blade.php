<div x-data="{
    mobile: window.matchMedia('(max-width:640px)').matches,
    activeView: @entangle('activeView'),
    init() {
        const mq = window.matchMedia('(max-width:640px)');
        mq.addEventListener('change', e => this.mobile = e.matches);
        if (this.mobile && this.activeView === 'month') this.activeView = 'agenda';
    }
}" class="relative">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 my-4">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button wire:click="previousMonth"
                x-on:click="$dispatch('global-overlay-start', { message: 'Loading calendar...' })"
                class="p-2 hover:bg-gray-200 rounded-full cursor-pointer" aria-label="Previous Month">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-800">{{ $this->monthName }}</h2>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" class="p-1 rounded hover:bg-gray-200" aria-label="Jump to month">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.away="open=false"
                        class="absolute z-20 mt-1 bg-white border border-gray-200 rounded shadow p-2 grid grid-cols-3 gap-1 w-56 left-[-10rem] right-auto sm:left-auto sm:right-0">
                        @php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
                        @foreach ($months as $idx => $label)
                            <button
                                @click="$wire.setMonth({{ $idx + 1 }}); open=false; $dispatch('global-overlay-start', { message: 'Loading calendar...' })"
                                class="text-xs px-2 py-1 rounded hover:bg-gray-100 flex items-center justify-between gap-2 {{ $currentMonth === $idx + 1 ? 'bg-primary/10 text-primary' : 'text-gray-700' }}">
                                <span>{{ $label }}</span>
                                @php $cnt = $monthlyTrainingCounts[$idx+1] ?? 0; @endphp
                                @if ($cnt > 0)
                                    <x-badge value="{{ $cnt }}" class="badge-neutral badge-xs" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            <button wire:click="nextMonth"
                x-on:click="$dispatch('global-overlay-start', { message: 'Loading calendar...' })"
                class="p-2 hover:bg-gray-200 rounded-full cursor-pointer" aria-label="Next Month">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        <div class="hidden sm:flex gap-2 sm:ml-auto">
            <button x-on:click="activeView='month'"
                :class="['px-3 py-1 rounded-md text-xs font-medium border shadow-sm cursor-pointer', activeView==='month' ?
                    'bg-primary text-white' : 'bg-white text-gray-600'
                ]">Month</button>
            <button x-on:click="activeView='agenda'"
                :class="['px-3 py-1 rounded-md text-xs font-medium border shadow-sm cursor-pointer', activeView==='agenda' ?
                    'bg-primary text-white' : 'bg-white text-gray-600'
                ]">Agenda</button>
        </div>
    </div>

    <div class="space-y-6">
        <div x-show="!mobile && activeView==='month'" x-cloak>
            <livewire:components.training.full-calendar :days="$days" :monthName="$this->monthName" :key="'cal-' . $currentYear . '-' . $currentMonth . '-' . $calendarVersion" />
        </div>
        <div x-show="mobile || activeView==='agenda'" x-cloak>
            <livewire:components.training.agenda-list :days="$days" :key="'agenda-' . $currentYear . '-' . $currentMonth . '-' . $calendarVersion" />
        </div>
    </div>
</div>
