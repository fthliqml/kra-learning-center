@props([
    'stage' => 'pretest',
    'stages' => ['pretest', 'module', 'posttest', 'result'],
    'progress' => null,
    'closeRoute' => null,
    'modules' => collect(),
    'activeModuleId' => null,
    'completedModuleIds' => [],
    'moduleRouteName' => null,
])

<aside x-cloak
    class="hidden md:flex shrink-0 border-r border-gray-200 bg-white flex-col shadow-[2px_0px_14px_-6px_rgba(0,0,0,0.10)] h-full relative transition-[width] duration-500 ease-in-out overflow-hidden"
    :class="openSidebar ? 'w-80' : 'w-0'" :aria-hidden="!openSidebar" :tabindex="openSidebar ? '0' : '-1'">
    <div class="flex-1 flex flex-col"
        :class="openSidebar ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-2 pointer-events-none'"
        style="transition:opacity 240ms ease, transform 300ms ease;">

        {{-- Progress Bar --}}
        @isset($progress)
            <div class="space-y-2.5 px-5 pt-5 pb-6 border-b" :class="openSidebar ? 'block' : 'hidden'">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-semibold tracking-wide text-gray-500 uppercase">Progress</span>
                    <span class="text-[11px] font-medium text-gray-800">{{ $progress }}%</span>
                </div>
                <div class="relative group" role="progressbar" aria-label="Course progress" aria-valuemin="0"
                    aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-valuetext="{{ $progress }} percent">
                    <div class="h-2.5 w-full rounded-full bg-gray-200/80 overflow-hidden">
                        <div
                            class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-[linear-gradient(110deg,rgba(255,255,255,0)_0%,rgba(255,255,255,0.6)_45%,rgba(255,255,255,0)_80%)] bg-[length:200%_100%] animate-[shimmer_2.2s_infinite] pointer-events-none">
                        </div>
                        <div class="h-full bg-primary/90 rounded-full transition-all duration-500 ease-out"
                            style="width: {{ $progress }}%"></div>
                        <div class="absolute top-1/2 -translate-y-1/2" style="left: calc({{ $progress }}% - 6px)">
                            <div class="w-3.5 h-3.5 rounded-full bg-white border-2 border-primary shadow-sm"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endisset

        {{-- Stages --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4" :class="openSidebar ? 'block' : 'hidden'">
            <ol class="space-y-0.5 text-[13px] font-medium">
                @foreach ($stages as $s)
                    @if ($s === 'module')
                        @if ($modules->count())
                            <li class="mt-2 mb-2" x-data="{ openModule: '{{ $activeModuleId ?? '' }}' }">
                                <p class="px-3 pt-2 text-[11px] font-semibold tracking-wide text-gray-400 uppercase">
                                    Modules
                                </p>
                                <ul class="space-y-1" role="list">
                                    @foreach ($modules as $m)
                                        @php
                                            $isActive = $activeModuleId && (string) $activeModuleId === (string) $m->id;
                                            $isCompleted = in_array($m->id, $completedModuleIds ?? []);
                                            $rowClasses = $isActive
                                                ? 'text-primary'
                                                : 'text-gray-700 hover:text-gray-900';
                                        @endphp
                                        <li class="border border-transparent rounded-md transition-colors bg-white"
                                            :class="openModule === 'module-{{ $m->id }}' ?
                                                'border-primary/30 bg-primary/5' :
                                                'hover:border-primary/10 hover:bg-gray-50'"
                                            x-data="{ id: 'module-{{ $m->id }}' }">
                                            <!-- Header button -->
                                            <button type="button" @click="openModule = openModule === id ? '' : id"
                                                class="w-full flex items-center gap-2 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-md group"
                                                :aria-expanded="openModule === id" :aria-controls="id + '-panel'"
                                                :class="openModule === id ? 'font-semibold' : 'font-medium'">
                                                <span class="relative w-4 h-4 flex items-center justify-center">
                                                    @if ($isCompleted)
                                                        <x-icon name="o-check-circle" class="size-4 text-green-500" />
                                                    @else
                                                        <span
                                                            class="w-2 h-2 rounded-full {{ $isActive ? 'bg-primary' : 'bg-gray-300 group-hover:bg-primary/50' }}"></span>
                                                    @endif
                                                </span>
                                                <span class="flex-1 truncate text-left" x-show="openSidebar"
                                                    x-transition.opacity.duration.200ms
                                                    title="{{ $m->title }}">{{ Str::limit($m->title, 60) }}</span>
                                                @if ($isActive)
                                                    <x-icon name="o-play-circle" class="size-4 text-primary" />
                                                @elseif($isCompleted)
                                                    <span
                                                        class="text-[10px] px-1.5 py-0.5 rounded bg-green-50 text-green-600 font-medium"
                                                        x-show="openSidebar" x-transition.opacity>Done</span>
                                                @endif
                                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-300"
                                                    :class="openModule === id ? 'rotate-180' : 'rotate-0'"
                                                    viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path d="M6 8l4 4 4-4" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                            <!-- Panel -->
                                            <div x-show="openModule === id" x-collapse x-cloak :id="id + '-panel'"
                                                class="px-3 pb-3 text-[11px] text-gray-600 space-y-2"
                                                @keydown.escape.stop="openModule = ''">
                                                <div class="flex items-center gap-3 flex-wrap" x-show="openSidebar"
                                                    x-transition.opacity.duration.200ms>
                                                    @php
                                                        $sectionCount =
                                                            $m->derived_section_count ?? ($m->sections->count() ?? 0);
                                                        $videoCount = $m->derived_video_count ?? 0;
                                                        $readingCount = $m->derived_reading_count ?? 0;
                                                    @endphp
                                                    <span class="inline-flex items-center gap-1 text-gray-500"><x-icon
                                                            name="o-document-text"
                                                            class="size-3 text-gray-400" />{{ $sectionCount }}
                                                        {{ Str::plural('Section', $sectionCount) }}</span>
                                                    @if (($videoCount ?? 0) > 0)
                                                        <span
                                                            class="inline-flex items-center gap-1 text-gray-500"><x-icon
                                                                name="o-play-circle"
                                                                class="size-3 text-gray-400" />{{ $videoCount }}
                                                            {{ Str::plural('Video', $videoCount) }}</span>
                                                    @endif
                                                    @if (($readingCount ?? 0) > 0)
                                                        <span
                                                            class="inline-flex items-center gap-1 text-gray-500"><x-icon
                                                                name="o-book-open"
                                                                class="size-3 text-gray-400" />{{ $readingCount }}
                                                            {{ Str::plural('Reading', $readingCount) }}</span>
                                                    @endif
                                                </div>
                                                @if ($moduleRouteName)
                                                    <a wire:navigate
                                                        href="{{ route($moduleRouteName, [$course->id ?? null]) }}"
                                                        class="mt-1 inline-flex items-center gap-1.5 text-primary text-[11px] font-medium hover:underline focus:outline-none focus:ring-1 focus:ring-primary/40 rounded px-1">Go
                                                        to Module<x-icon name="o-arrow-right" class="size-3" /></a>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @else
                        @php $isStageActive = $stage === $s; @endphp
                        <li class="group">
                            <button type="button"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="stage === '{{ $s }}' ? 'bg-primary/5 text-primary font-semibold' :
                                    'text-gray-600 hover:bg-gray-50'">
                                <span class="flex items-center gap-2 truncate"
                                    :class="!openSidebar && 'justify-center w-full'">
                                    <span class="capitalize" x-show="openSidebar"
                                        x-transition.opacity>{{ ucfirst($s) }}</span>
                                    <span class="capitalize" x-show="!openSidebar" x-transition.opacity>
                                        {{ strtoupper(substr($s, 0, 1)) }}
                                    </span>
                                </span>
                                <span class="relative w-4 h-4 flex items-center justify-center">
                                    <span x-show="stage !== '{{ $s }}'" x-cloak
                                        class="absolute inset-0 rounded-full border border-gray-300 transition-colors group-hover:border-primary/40"></span>
                                    <span x-show="stage === '{{ $s }}'" x-cloak
                                        class="absolute inset-0 rounded-full bg-primary"></span>
                                    <span x-show="stage === '{{ $s }}'" x-cloak
                                        class="absolute rounded-full bg-white" style="inset:4px"></span>
                                </span>
                            </button>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>

        {{-- Close Button --}}
        <div class="px-4 py-3 border-t border-gray-200/70" :class="openSidebar ? 'block' : 'hidden'">
            <a href="{{ $closeRoute ?? '#' }}" wire:navigate
                class="w-full inline-flex items-center justify-center gap-2 text-xs font-medium px-4 py-2.5 rounded-md bg-red-600 text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400/60 transition"
                aria-label="Close Course">
                <x-icon name="o-x-mark" class="size-4" />
                <span>Close Course</span>
            </a>
        </div>
        {{-- Collapsed rail removed (width becomes 0) --}}
    </div>
</aside>
