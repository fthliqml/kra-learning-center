<div>
  {{-- Header --}}
  <div class="w-full flex flex-col lg:flex-row gap-6 lg:gap-5 mb-5 lg:mb-9 items-start lg:items-center">
    <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
      Survey Template
    </h1>

    <div class="flex gap-2 flex-col w-full items-center justify-center lg:justify-end sm:flex-row flex-1">
      {{-- Default Template Settings Button --}}
      <x-button wire:click="$dispatch('open-default-template-modal')" class="btn-outline btn-primary h-10"
        title="Set default templates for each competency group">
        <x-icon name="o-cog-6-tooth" class="size-4" />
        <span class="hidden sm:inline">Defaults</span>
      </x-button>

      <x-ui.button variant="primary" wire:click="addPage" wire:target="addPage" class="h-10"
        wire:loading.attr="readonly">
        <span wire:loading.remove wire:target="addPage" size="lg" class="flex items-center gap-2">
          <x-icon name="o-plus" class="size-4" />
          Add
        </span>
        <span wire:loading wire:target="addPage">
          <x-icon name="o-arrow-path" class="size-4 animate-spin" />
        </span>
      </x-ui.button>

      <x-select wire:model.live="filter" :options="$filterOptions" option-value="value" option-label="label"
        placeholder="All Levels"
        class="!w-32 !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
        icon-right="o-funnel" />

      <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
    </div>
  </div>

  <x-skeletons.survey-template-skeleton />

  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
    @forelse ($surveyTemplates as $surveyTemplate)
      <a wire:loading.remove
        href="{{ route('survey-template.edit', ['level' => $surveyTemplate->level, 'surveyId' => $surveyTemplate->id]) }}"
        class="card bg-base-100 border border-primary/20 shadow h-44 transition duration-200 hover:shadow-md hover:border-primary/60 hover:bg-primary/5 hover:-translate-y-1 cursor-pointer block">
        <div class="card-body p-4 md:p-5 flex flex-col h-full">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <h3 class="text-sm sm:text-base md:text-lg font-semibold text-base-content/90 leading-snug truncate"
                title="{{ $surveyTemplate->title }}">
                {{ $surveyTemplate->title }}
              </h3>
              <p class="text-[11px] md:text-xs text-base-content/60 mt-1 break-words"
                style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;"
                title="{{ $surveyTemplate->description }}">
                {{ $surveyTemplate->description }}
              </p>
            </div>

            @php
              $status = $surveyTemplate->status ?? '';
              $badgeClass = match ($status) {
                'draft' => 'badge-warning',
                'active' => 'badge-primary bg-primary/95',
                default => 'badge-primary bg-primary/95',
              };
            @endphp

            <x-badge :value="str($status)->title()" class="{{ $badgeClass }} badge-xs sm:badge-sm" />
          </div>

          @php
            $level = $surveyTemplate->level ?? '';
            $colorClasses = match ($level) {
              1 => ['badge' => 'border-green-700 bg-green-50'],
              3 => ['badge' => 'border-indigo-700 bg-indigo-50'],
              default => ['badge' => 'border-primary bg-[#E4F3FF]'],
            };
          @endphp

          <div class="mt-auto pt-3 flex items-center justify-between">
            <div class="flex items-center gap-2 flex-wrap">
              @if ($level)
                <span
                  class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-[10px] sm:text-[11px] border {{ $colorClasses['badge'] }}">
                  Level {{ $level }}
                </span>
              @endif
            </div>
          </div>
        </div>
      </a>
    @empty
      <div class="col-span-full">
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
          <div class="flex flex-col items-center justify-center py-16 px-4">
            <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
            <p class="text-sm text-gray-500 text-center">
              There are no survey templates available.
            </p>
          </div>
        </div>
      </div>
    @endforelse
  </div>

  <div class="mt-4" wire:loading.remove>
    {{ $surveyTemplates->links('pagination::tailwind') }}
  </div>

  {{-- Default Template Modal --}}
  <livewire:components.survey-template.default-template-modal />
</div>