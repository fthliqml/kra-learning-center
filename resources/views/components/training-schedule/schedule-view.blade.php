<div x-data="{
    mobile: window.matchMedia('(max-width:640px)').matches,
    activeView: @entangle('activeView'),
    // Global loading state for modal operations
    modalLoading: false,
    init() {
        const mq = window.matchMedia('(max-width:640px)');
        mq.addEventListener('change', e => this.mobile = e.matches);
        if (this.mobile && this.activeView === 'month') this.activeView = 'agenda';
    }
}"
x-on:calendar-loading-start.window="modalLoading = true"
x-on:training-modal-opened.window="modalLoading = false"
x-on:training-detail-ready.window="modalLoading = false"
x-on:open-action-choice.window="modalLoading = false"
class="relative">

    <!-- Global Loading Overlay - z-40 agar di bawah modal (z-50+) tapi di atas calendar -->
    <div x-show="modalLoading" x-cloak x-transition:enter="transition ease-out duration-100"
         x-on:click.stop.prevent
         x-init="$watch('modalLoading', value => {
             if (value) {
                 document.body.style.overflow = 'hidden';
             } else {
                 document.body.style.overflow = '';
             }
         })"
         class="fixed inset-0 bg-black/30 backdrop-blur-sm z-[100] flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl p-6 flex flex-col items-center gap-3">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <span class="text-sm text-gray-600 font-medium">Loading...</span>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 my-4">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button wire:click="previousMonth"
                class="p-2 hover:bg-gray-200 rounded-full cursor-pointer tooltip tooltip-bottom" data-tip="previous month"
                aria-label="Previous Month">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-800">{{ $this->monthName }}</h2>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" class="p-1 rounded hover:bg-gray-200 tooltip tooltip-bottom"
                        data-tip="jump to" aria-label="Jump to month">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.away="open=false"
                        class="absolute z-20 mt-1 bg-white border border-gray-200 rounded shadow p-2 grid grid-cols-3 gap-1 w-56 left-[-10rem] right-auto sm:left-auto sm:right-0">
                        @php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
                        @foreach ($months as $idx => $label)
                            <button @click="$wire.setMonth({{ $idx + 1 }}); open=false"
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
                class="p-2 hover:bg-gray-200 rounded-full cursor-pointer tooltip tooltip-bottom" data-tip="next month"
                aria-label="Next Month">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        <div class="hidden sm:flex gap-2 sm:ml-auto">
            <button wire:click="setView('month')"
                :class="['px-3 py-1 rounded-md text-xs font-medium border shadow-sm cursor-pointer', activeView==='month' ?
                    'bg-primary text-white' : 'bg-white text-gray-600'
                ]">Month</button>
            <button wire:click="setView('agenda')"
                :class="['px-3 py-1 rounded-md text-xs font-medium border shadow-sm cursor-pointer', activeView==='agenda' ?
                    'bg-primary text-white' : 'bg-white text-gray-600'
                ]">Agenda</button>
        </div>
    </div>

    <div class="space-y-6">
        @php
            $hasFilters = $filterTrainerId || $filterType;
        @endphp
        @if ($hasFilters)
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-gray-500 mr-1">Filters:</span>
                @if ($filterTrainerId)
                    <span class="inline-flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded-full">
                        <span>Trainer: {{ $filterTrainerName }}</span>
                        <button class="hover:text-red-500" wire:click="onFiltersUpdated(null, '{{ $filterType }}')"
                            x-on:click="$dispatch('schedule-clear-trainer')"
                            aria-label="Clear trainer filter">&times;</button>
                    </span>
                @endif
                @if ($filterType)
                    <span class="inline-flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded-full">
                        <span>Type: {{ $filterType }}</span>
                        <button class="hover:text-red-500" wire:click="onFiltersUpdated('{{ $filterTrainerId }}', null)"
                            x-on:click="$dispatch('schedule-clear-type')"
                            aria-label="Clear type filter">&times;</button>
                    </span>
                @endif
                <button class="ml-2 text-gray-500 hover:text-gray-700 underline"
                    wire:click="onFiltersUpdated(null, null)" x-on:click="$dispatch('schedule-clear-all')">Clear
                    All</button>
            </div>
        @endif
        <div x-show="!mobile && activeView==='month'" x-cloak>
            <livewire:components.training-schedule.full-calendar :days="$days" :monthName="$this->monthName" :key="'cal-' . $currentYear . '-' . $currentMonth . '-' . $calendarVersion" lazy />
        </div>
        <div x-show="mobile || activeView==='agenda'" x-cloak>
            <livewire:components.training-schedule.agenda-list :days="$days" :key="'agenda-' . $currentYear . '-' . $currentMonth . '-' . $calendarVersion" lazy />
        </div>
    </div>

    <livewire:components.shared.action-choice-modal />
</div>
