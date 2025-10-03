@props([
    'stage' => 'pretest',
    'stages' => ['pretest', 'module', 'posttest', 'result'],
    'progress' => null,
    'closeRoute' => null,
    'modules' => collect(),
])

<div x-show="mobileSidebar" x-cloak class="fixed inset-0 z-50 md:hidden">
    <div @click="mobileSidebar=false" x-transition:enter="transition-all ease-out duration-300"
        x-transition:enter-start="opacity-0 backdrop-blur-none" x-transition:enter-end="opacity-100 backdrop-blur-sm"
        x-transition:leave="transition-all ease-in duration-250" x-transition:leave-start="opacity-100 backdrop-blur-sm"
        x-transition:leave-end="opacity-0 backdrop-blur-none"
        class="absolute inset-0 bg-black/40 will-change-[opacity,filter] z-0"></div>
    <aside x-show="mobileSidebar" x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="absolute top-0 right-0 h-full w-72 bg-white shadow-xl flex flex-col will-change-transform z-10">

        {{-- Course Content Title --}}
        <div class="p-4 border-b flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Course Content</h2>
            <button type="button" @click="mobileSidebar=false"
                class="inline-flex items-center justify-center size-8 rounded-lg hover:bg-gray-100 text-gray-600">
                <x-icon name="o-x-mark" class="size-5" />
            </button>
        </div>

        {{-- Progress Bar --}}
        @isset($progress)
            <div class="px-4 pt-4 pb-5 border-b space-y-2">
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
        <nav class="flex-1 overflow-y-auto p-4">
            <ol class="space-y-3 text-sm">
                @foreach ($stages as $s)
                    @if ($s === 'module')
                        @if ($modules->count())
                            <li class="mt-1 mb-2" x-data="{ openModule: '' }">
                                <ul class="space-y-1" role="list">
                                    @foreach ($modules as $m)
                                        @php
                                            $isActive = false; // mobile does not have activeModuleId context passed
                                            $isCompleted = false; // can be wired later
                                        @endphp
                                        <li class="group relative border border-transparent rounded-md bg-white transition-colors"
                                            :class="openModule === 'm-{{ $m->id }}' ?
                                                'border-primary/40 bg-primary/5 shadow-sm' :
                                                'hover:border-primary/20 hover:bg-gray-50'"
                                            x-data="{ id: 'm-{{ $m->id }}' }">
                                            <button type="button" @click="openModule = openModule === id ? '' : id"
                                                class="w-full flex items-center gap-2 pl-4 pr-3 py-2 text-left focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-md"
                                                :aria-expanded="openModule === id" :aria-controls="id + '-panel'"
                                                :class="openModule === id ? 'font-semibold text-primary' :
                                                    'font-medium text-gray-700'">
                                                <span class="flex-1 truncate text-left leading-tight"
                                                    title="{{ $m->title }}">
                                                    {{ Str::limit($m->title, 60) }}
                                                </span>
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
                                            <div x-show="openModule === id" x-collapse x-cloak :id="id + '-panel'"
                                                class="pl-6 pr-4 pb-3 text-[11px] text-gray-600 space-y-2">
                                                @php $sections = $m->sections ?? collect(); @endphp
                                                @if ($sections->count())
                                                    <ul class="space-y-1.5">
                                                        @foreach ($sections as $index => $sec)
                                                            @php $sectionCompleted = false; @endphp
                                                            <li class="flex items-start gap-2 group/section">
                                                                <span
                                                                    class="mt-1 w-3.5 h-3.5 flex items-center justify-center">
                                                                    @if ($sectionCompleted)
                                                                        <x-icon name="o-check"
                                                                            class="size-3 text-green-600" />
                                                                    @else
                                                                        <span
                                                                            class="w-1.5 h-1.5 rounded-full bg-gray-300 group-hover/section:bg-primary/60 transition"></span>
                                                                    @endif
                                                                </span>
                                                                <span
                                                                    class="flex-1 pt-1 text-[11px] leading-snug text-gray-600 group-hover/section:text-gray-800 truncate"
                                                                    title="{{ $sec->title ?? 'Section ' . ($index + 1) }}">
                                                                    {{ Str::limit($sec->title ?? 'Section ' . ($index + 1), 70) }}
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
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
                        <li class="group relative border border-transparent rounded-md bg-white transition-colors"
                            :class="stage === '{{ $s }}' ? 'border-primary/40 bg-primary/5 shadow-sm' :
                                'hover:border-primary/20 hover:bg-gray-50'">
                            <button type="button"
                                class="w-full flex items-center gap-2 pl-4 pr-3 py-2 text-left focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-md"
                                :class="stage === '{{ $s }}' ? 'font-semibold text-primary' :
                                    'font-medium text-gray-700'">
                                <span class="flex-1 truncate capitalize">{{ ucfirst($s) }}</span>
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

        {{-- Close Button --}}
        <div class="p-4 border-t">
            <a href="{{ $closeRoute ?? '#' }}" wire:navigate
                class="w-full inline-flex items-center justify-center gap-2 text-xs font-medium px-4 py-2.5 rounded-md bg-gray-900/5 text-gray-700 hover:bg-gray-900/10 focus:outline-none focus:ring-2 focus:ring-gray-400/40 transition border border-gray-300/60"
                aria-label="Close Course">
                <x-icon name="o-x-mark" class="size-4" />
                <span>Close Course</span>
            </a>
        </div>
    </aside>
</div>
