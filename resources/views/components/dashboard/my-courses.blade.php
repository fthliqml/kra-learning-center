<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <x-mary-icon name="o-academic-cap" class="w-5 h-5 text-secondary" />
            My Courses
        </h3>
        <a wire:navigate href="{{ route('courses.index') }}"
            class="text-sm text-secondary hover:text-secondary/80 font-medium">
            View More →
        </a>
    </div>

    {{-- Courses List --}}
    <div class="space-y-3 max-h-[500px] overflow-y-auto">
        @forelse($courses as $course)
            <a wire:navigate href="{{ route('courses-overview.show', $course['id']) }}"
                class="block rounded-xl border border-gray-200 dark:border-gray-700 shadow-md p-4 bg-gradient-to-r from-sky-50 to-white dark:from-sky-900/20 dark:to-gray-800 hover:shadow-lg transition-all">
                <div class="flex gap-4">
                    {{-- Thumbnail --}}
                    <div class="shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700">
                        <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}"
                            class="w-full h-full object-cover">
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        {{-- Category --}}
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                            {{ $course['category'] }}
                        </p>

                        {{-- Title --}}
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white line-clamp-2 mb-2">
                            {{ $course['title'] }}
                        </h4>

                        {{-- Progress Bar --}}
                        <div class="space-y-1">
                            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-2 bg-blue-600 dark:bg-blue-500 rounded-full transition-all duration-300"
                                    style="width: {{ $course['progress'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 text-right">
                                {{ $course['progress'] }}% Complete
                            </p>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-12">
                <x-mary-icon name="o-academic-cap" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No courses in progress</p>
            </div>
        @endforelse
    </div>
</div>
