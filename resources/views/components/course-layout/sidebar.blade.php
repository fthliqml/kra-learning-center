@props([
    'stage' => 'pretest',
    'stages' => ['pretest', 'module', 'posttest', 'result'],
    'progress' => null,
    'closeRoute' => null,
    'modules' => collect(),
    'activeModuleId' => null,
    'activeSectionId' => null,
    'completedModuleIds' => [],
    'moduleRouteName' => null,
])

<aside x-cloak
    class="hidden md:flex shrink-0 border-r border-gray-200 bg-white flex-col shadow-[2px_0px_14px_-6px_rgba(0,0,0,0.10)] h-full relative transition-[width] duration-500 ease-in-out overflow-hidden"
    :class="openSidebar ? 'w-65' : 'w-0'" :aria-hidden="!openSidebar" :tabindex="openSidebar ? '0' : '-1'">
    <div class="flex-1 flex flex-col"
        :class="openSidebar ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-2 pointer-events-none'"
        style="transition:opacity 240ms ease, transform 300ms ease;">

        {{-- Progress Bar --}}
        @isset($progress)
            <div class="space-y-2 px-5 pt-5 pb-6 border-b" :class="openSidebar ? 'block' : 'hidden'">
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
                    {{-- Learning Module --}}
                    @if ($s === 'module')
                        @if ($modules->count())
                            <li class="mt-2 mb-2" x-data="{ openModule: 'module-{{ $activeModuleId ?? '' }}' }">
                                <ul class="space-y-1" role="list">
                                    @foreach ($modules as $m)
                                        @php
                                            $isActive = $activeModuleId && (string) $activeModuleId === (string) $m->id;
                                            $isCompleted =
                                                (isset($m->is_completed) && $m->is_completed) ||
                                                in_array($m->id, $completedModuleIds ?? []);
                                            $rowClasses = $isActive
                                                ? 'text-primary'
                                                : 'text-gray-700 hover:text-gray-900';
                                        @endphp
                                        <li class="group relative border border-transparent rounded-md bg-white transition-colors"
                                            :class="openModule === 'module-{{ $m->id }}' ?
                                                'border-primary/40 bg-primary/5 shadow-sm' :
                                                'hover:border-primary/20 hover:bg-gray-50'"
                                            x-data="{ id: 'module-{{ $m->id }}' }">
                                            <button type="button" @click="openModule = openModule === id ? '' : id"
                                                @if ($stage === 'module') wire:click="selectTopic({{ $m->id }})" @endif
                                                class="w-full flex items-center gap-2 pl-4 pr-3 py-2.5 text-left focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-md"
                                                :aria-expanded="openModule === id" :aria-controls="id + '-panel'"
                                                :class="openModule === id ? 'font-semibold text-primary' :
                                                    'font-medium text-gray-700'">

                                                {{-- Topic Title (Sidebar Open) --}}
                                                <span class="flex-1 truncate text-left leading-tight"
                                                    x-show="openSidebar" x-transition.opacity.duration.200ms
                                                    title="{{ $m->title }}">
                                                    <span class="block">{{ Str::limit($m->title, 60) }}</span>
                                                </span>

                                                {{-- Topic Title (Sidebar Close) --}}
                                                <span class="truncate" x-show="!openSidebar" x-transition.opacity
                                                    title="{{ $m->title }}">
                                                    {{ Str::limit($m->title, 1) }}
                                                </span>

                                                {{-- Topic Indicator --}}
                                                <span class="relative w-5 h-5 flex items-center justify-center ml-0.5">
                                                    @if ($isCompleted)
                                                        <x-icon name="o-check-circle" class="size-5 text-green-500" />
                                                    @elseif($isActive)
                                                        <span class="absolute inset-0 rounded-full bg-primary"></span>
                                                        <span class="absolute rounded-full bg-white"
                                                            style="inset:5px"></span>
                                                    @else
                                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-primary transition"
                                                            viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                            <circle cx="10" cy="10" r="8"
                                                                stroke="currentColor" stroke-width="1.5" />
                                                            <path d="M10 6v8M6 10h8" stroke="currentColor"
                                                                stroke-width="1.5" stroke-linecap="round" />
                                                        </svg>
                                                    @endif
                                                </span>
                                            </button>

                                            <!-- Sections -->
                                            <div x-show="openModule === id" x-collapse x-cloak :id="id + '-panel'"
                                                class="pl-7 pr-4 pb-3 text-[11px] text-gray-600 space-y-2"
                                                @keydown.escape.stop="openModule = ''">
                                                @php
                                                    $sections = $m->sections ?? collect();
                                                @endphp
                                                @if ($sections->count())
                                                    <ul class="space-y-1.5" x-show="openSidebar"
                                                        x-transition.opacity.duration.200ms>
                                                        @foreach ($sections as $index => $sec)
                                                            @php
                                                                $sectionCompleted = isset($sec->is_completed)
                                                                    ? (bool) $sec->is_completed
                                                                    : false;
                                                                $sectionActive =
                                                                    (string) ($activeModuleId ?? '') ===
                                                                        (string) $m->id &&
                                                                    (string) ($activeSectionId ?? '') ===
                                                                        (string) $sec->id;
                                                            @endphp
                                                            <li class="flex items-start gap-2 group/section">
                                                                {{-- Section Indicator (match stage style) --}}
                                                                <span
                                                                    class="relative mt-0.5 w-4 h-4 flex items-center justify-center">
                                                                    @if ($sectionCompleted)
                                                                        <x-icon name="o-check-circle"
                                                                            class="size-4 text-green-500" />
                                                                    @elseif($sectionActive)
                                                                        <span
                                                                            class="w-2 h-2 rounded-full bg-primary"></span>
                                                                    @else
                                                                        <span
                                                                            class="w-1.5 h-1.5 rounded-full bg-gray-300 group-hover/section:bg-primary/60 transition"></span>
                                                                    @endif
                                                                </span>

                                                                {{-- Section Title --}}
                                                                <button type="button"
                                                                    @if ($stage === 'module') wire:click="selectSection({{ $sec->id }})" @endif
                                                                    class="flex-1 text-left pt-1 text-[11px] leading-snug truncate rounded hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-primary/30 px-1 {{ $sectionActive ? 'text-primary font-semibold' : 'text-gray-600 group-hover/section:text-gray-800' }}"
                                                                    title="{{ $sec->title ?? 'Section ' . ($index + 1) }}">
                                                                    {{ Str::limit($sec->title ?? 'Section ' . ($index + 1), 70) }}
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif

                                                {{-- Module Link --}}
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
                        @php
                            $isStageActive = $stage === $s;
                            $stageOrder = array_values($stages);
                            $currentIndex = array_search($stage, $stageOrder, true);
                            $thisIndex = array_search($s, $stageOrder, true);
                            $isCompletedStage =
                                $thisIndex !== false && $currentIndex !== false && $thisIndex < $currentIndex;
                        @endphp

                        {{-- Stages --}}
                        <li class="group relative border border-transparent rounded-md bg-white transition-colors"
                            :class="stage === '{{ $s }}' ? 'border-primary/40 bg-primary/5 shadow-sm' :
                                'hover:border-primary/20 hover:bg-gray-50'">
                            <button type="button"
                                class="w-full flex items-center gap-2 pl-4 pr-3 py-2.5 text-left focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-md"
                                :class="stage === '{{ $s }}' ? 'font-semibold text-primary' :
                                    'font-medium text-gray-700'">

                                {{-- Stage Name (Sidebar Open) --}}
                                <span class="flex-1 truncate capitalize" x-show="openSidebar" x-transition.opacity>
                                    {{ ucfirst($s) }}
                                </span>

                                {{-- Stage Initial (Sidebar Close) --}}
                                <span class="capitalize" x-show="!openSidebar" x-transition.opacity>
                                    {{ strtoupper(substr($s, 0, 1)) }}
                                </span>

                                {{-- Stage Indicator --}}
                                <span class="relative w-5 h-5 flex items-center justify-center ml-0.5">
                                    @if ($isCompletedStage)
                                        <x-icon name="o-check-circle" class="size-5 text-green-500" />
                                    @elseif($isStageActive)
                                        <span class="absolute inset-0 rounded-full bg-primary"></span>
                                        <span class="absolute rounded-full bg-white" style="inset:5px"></span>
                                    @else
                                        <span
                                            class="absolute inset-0 rounded-full border border-gray-300 transition-colors group-hover:border-primary/40"></span>
                                    @endif
                                </span>
                            </button>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
</aside>
