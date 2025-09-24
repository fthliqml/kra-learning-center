<div x-data="{
    mobile: window.matchMedia('(max-width:640px)').matches,
    activeView: @entangle('activeView'),
    init() {
        const mq = window.matchMedia('(max-width:640px)');
        mq.addEventListener('change', e => this.mobile = e.matches);
        if (this.mobile && this.activeView === 'month') this.activeView = 'agenda';
    }
}" class="relative">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button wire:click="previousMonth" class="p-2 hover:bg-gray-200 rounded-full cursor-pointer"
                aria-label="Previous Month">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-800">{{ $this->monthName }}</h2>
            <button wire:click="nextMonth" class="p-2 hover:bg-gray-200 rounded-full cursor-pointer"
                aria-label="Next Month">
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
            <livewire:components.training.full-calendar :days="$days" :monthName="$this->monthName" :key="'cal-' . $currentYear . '-' . $currentMonth" />
        </div>
        <div x-show="mobile || activeView==='agenda'" x-cloak>
            <livewire:components.training.agenda-list :days="$days" :key="'agenda-' . $currentYear . '-' . $currentMonth" />
        </div>
    </div>
    <div wire:loading.delay.short wire:target="previousMonth,nextMonth,refreshTrainings"
        class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/60 backdrop-blur-[2px] pointer-events-none">
        <div class="flex items-center gap-3 text-primary">
            <svg class="animate-spin h-7 w-7 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="font-medium text-sm tracking-wide" aria-live="polite">Loading...</span>
        </div>
        <span class="mt-2 text-[11px] text-gray-500">Preparing data...</span>
    </div>
</div>
