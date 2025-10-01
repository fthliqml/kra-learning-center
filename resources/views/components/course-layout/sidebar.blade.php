@props([
    'stage' => 'pretest',
    'stages' => ['pretest', 'module', 'posttest', 'result'],
    'progress' => null,
    'closeRoute' => null,
    'modules' => collect(),
])

<aside x-show="openSidebar" x-transition:enter="transform transition ease-out duration-300"
    x-transition:enter-start="-translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0" x-cloak
    class="hidden md:flex w-72 shrink-0 border-r border-gray-200 bg-white flex-col shadow-[2px_0px_14px_-4px_rgba(0,0,0,0.12)] h-full will-change-transform">

    {{-- Course Content Title --}}
    <div class="px-6 py-4 border-b space-y-3">
        <h2 class="text-sm font-semibold text-gray-900">Course Content</h2>
    </div>

    {{-- Progress Bar --}}
    @isset($progress)
        <div class="space-y-2 px-6 pt-5 pb-7 border-b">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-gray-600 uppercase">Progress</span>
                <span class="text-[11px] font-semibold text-gray-900">{{ $progress }}%</span>
            </div>
            <div class="relative group" role="progressbar" aria-label="Course progress" aria-valuemin="0"
                aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-valuetext="{{ $progress }} percent">
                <div
                    class="h-3 w-full rounded-full bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 overflow-hidden">
                    <div
                        class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-[linear-gradient(110deg,rgba(255,255,255,0)_0%,rgba(255,255,255,0.6)_45%,rgba(255,255,255,0)_80%)] bg-[length:200%_100%] animate-[shimmer_2.2s_infinite] pointer-events-none">
                    </div>
                    <div class="h-full bg-gradient-to-r from-primary to-primary/70 rounded-full transition-all duration-500 ease-out"
                        style="width: {{ $progress }}%"></div>
                    <div class="absolute top-1/2 -translate-y-1/2" style="left: calc({{ $progress }}% - 8px)">
                        <div class="w-4 h-4 rounded-full bg-white border-2 border-primary shadow ring-2 ring-white"></div>
                    </div>
                </div>
            </div>
        </div>
    @endisset

    {{-- Stages --}}
    <nav class="flex-1 overflow-y-auto px-4 py-5">
        <ol class="space-y-1 text-sm font-medium">
            @foreach ($stages as $s)
                <li class="group border-b last:border-b-0 border-gray-300/30 pb-1">
                    <div class="flex items-center justify-between rounded-md px-3 py-3 cursor-default"
                        :class="stage === '{{ $s }}' ? 'text-primary font-semibold' :
                            'text-gray-700 hover:bg-gray-50'">
                        <div class="flex items-center gap-2">
                            <span class="capitalize">
                                {{ $s === 'module' ? 'Learning Module' : ucfirst($s) }}
                            </span>
                        </div>
                        <span class="relative w-4 h-4 flex items-center justify-center">
                            <span x-show="stage !== '{{ $s }}'" x-cloak
                                class="absolute inset-0 rounded-full border border-gray-300 transition-colors group-hover:border-primary/40"></span>
                            <span x-show="stage === '{{ $s }}'" x-cloak
                                class="absolute inset-0 rounded-full bg-primary"></span>
                            <span x-show="stage === '{{ $s }}'" x-cloak
                                class="absolute rounded-full bg-white" style="inset:4px"></span>
                        </span>
                    </div>
                    @if ($s === 'module' && $modules->count())
                        <ul class="mt-1 mb-2 ml-4 space-y-1">
                            @foreach ($modules as $m)
                                <li class="flex items-center gap-2 text-[13px] text-gray-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    <span class="truncate"
                                        title="{{ $m->title }}">{{ Str::limit($m->title, 38) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    {{-- Close Button --}}
    <div class="px-4 py-3 border-t">
        <a href="{{ $closeRoute ?? '#' }}" wire:navigate
            class="w-full inline-flex items-center justify-center gap-2 text-xs font-medium px-4 py-2.5 rounded-md bg-red-600 text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400/60 transition"
            aria-label="Close Course">
            <x-icon name="o-x-mark" class="size-4" />
            <span>Close Course</span>
        </a>
    </div>
</aside>
