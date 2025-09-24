<div>

    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Courses
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-4 md:flex-row">
            <x-search-input placeholder="Search..." class="max-w-72" input-class="!rounded-full "
                wire:model.live="search" />

            <div class="flex items-center justify-center gap-2">
                <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="Filter"
                    class="!w-30 !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer rounded-full shadow-sm [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>
        </div>
    </div>
</div>
