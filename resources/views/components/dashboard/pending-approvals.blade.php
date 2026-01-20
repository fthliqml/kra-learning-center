<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">Pending Approvals</h3>
        @if (count($items) > 0)
            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full whitespace-nowrap">
                {{ count($items) }} items
            </span>
        @endif
    </div>

    {{-- List of Pending Items --}}
    @if (count($items) > 0)
        <div
            class="space-y-3 max-h-[280px] overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
            @foreach ($items as $item)
                <a href="{{ $this->getUrl($item['type'], $item['id']) }}"
                    class="relative flex justify-between items-center w-full p-4 pl-5 rounded-xl bg-gray-50 dark:bg-gray-700/50 shadow-sm hover:shadow-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-all cursor-pointer overflow-hidden gap-3">
                    {{-- Gradient Strip --}}
                    <div
                        class="absolute left-0 top-0 h-full w-1.5 rounded-l-xl bg-gradient-to-b {{ $this->gradient($item['type']) }}">
                    </div>

                    {{-- Left Content --}}
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="shrink-0 p-2 rounded-lg bg-white dark:bg-gray-600 shadow-sm">
                            <x-mary-icon name="{{ $this->iconName($item['type']) }}"
                                class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                        </div>
                        <div class="min-w-0 truncate">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $item['title'] }}</p>
                            @if ($item['type'] !== 'idp')
                                <p class="text-xs text-gray-500 dark:text-gray-400 capitalize truncate">
                                    {{ str_replace('_', ' ', $item['type']) }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Right Content --}}
                    <div class="text-right shrink-0">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $item['info'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
            <x-mary-icon name="o-check-circle" class="w-12 h-12 mb-2" />
            <p class="text-sm">No pending approvals</p>
        </div>
    @endif
</div>
