<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-6">
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
    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
        @forelse($courses as $course)
            <a wire:navigate href="{{ route('courses-overview.show', $course['id']) }}"
                class="block rounded-2xl border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:shadow-md transition">
                <div class="flex gap-4">
                    {{-- Thumbnail --}}
                    <div
                        class="shrink-0 w-28 sm:w-32 aspect-[16/9] rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700">
                        @if (!empty($course['thumbnail_url']))
                            <img src="{{ Storage::url($course['thumbnail_url']) }}" alt="{{ $course['title'] }}"
                                class="w-full h-full object-cover" loading="lazy">
                        @else
                            <div
                                class="h-full w-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800">
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            {{-- Category --}}
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $course['category'] }}
                            </p>

                            {{-- Status --}}
                            @php
                                $status = $course['status'] ?? 'not_started';
                                $label = $course['is_completed']
                                    ? 'Completed'
                                    : ($status === 'in_progress'
                                        ? 'In Progress'
                                        : 'Not Started');
                                $badge = $course['is_completed']
                                    ? 'badge-success'
                                    : ($status === 'in_progress'
                                        ? 'badge-info'
                                        : 'badge-ghost');
                            @endphp
                            <span class="badge badge-outline {{ $badge }}" title="{{ $label }}">
                                {{ $label }}
                            </span>
                        </div>

                        {{-- Title --}}
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white line-clamp-2 mt-1 mb-2">
                            {{ $course['title'] }}
                        </h4>

                        @if (!empty($course['last_activity']))
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2"
                                title="{{ $course['last_activity_at'] }}">
                                Last activity: {{ $course['last_activity'] }}
                            </p>
                        @endif

                        {{-- Progress Bar --}}
                        <div class="space-y-1">
                            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-2 bg-blue-600 dark:bg-blue-500 rounded-full transition-all duration-300"
                                    style="width: {{ $course['progress'] }}%"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $course['is_completed'] ? 'Completed' : (($course['status'] ?? 'not_started') === 'not_started' ? 'Not Started' : 'Progress') }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $course['progress'] }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-12">
                <x-mary-icon name="o-academic-cap" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No courses yet</p>
            </div>
        @endforelse
    </div>
</div>
