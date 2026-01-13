<div class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative min-h-[50vh]" x-data="{ loading: false }"
    @click="if ($event.target.closest('a[wire\\:navigate]')) { loading = true; }">

    {{-- Navigation Loading Overlay (Content Only) --}}
    <template x-if="loading">
        <div class="absolute inset-0 z-50 bg-white/90 backdrop-blur-sm flex items-center justify-center rounded-xl">
            <div class="flex flex-col items-center gap-4 p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
                <div class="w-12 h-12 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
                <span class="text-base text-gray-700 font-medium">Loading results...</span>
            </div>
        </div>
    </template>

    {{-- Historical Attempt Banner --}}
    @if ($isHistorical)
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3">
            <x-icon name="o-information-circle" class="size-5 text-blue-500 flex-shrink-0" />
            <div class="text-sm text-blue-800">
                <span class="font-medium">Viewing historical data.</span>
                This is a result from a previous attempt, not your latest one.
            </div>
        </div>
    @endif

    {{-- Heading --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            Result
            @if ($currentAttemptNum > 0)
                <span class="text-lg font-medium text-gray-500 ml-1">#{{ $currentAttemptNum }}</span>
            @endif
        </h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Status Card --}}
        <section
            class="rounded-xl border border-{{ $statusColor }}-200 bg-{{ $statusColor }}-50 p-6 md:col-span-1 flex flex-col items-center justify-center text-center">
            <div class="rounded-full bg-white/70 border border-{{ $statusColor }}-200 p-3 mb-3">
                <x-icon :name="$statusIcon" class="size-10 text-{{ $statusColor }}-600" />
            </div>

            <div class="text-xs font-medium text-{{ $statusColor }}-700 uppercase tracking-wider">Status</div>
            <div class="mt-1 text-2xl font-bold text-{{ $statusColor }}-900">{{ $statusLabel }}</div>

            @if ($topAttempt)
                @if ($essayPending)
                    <div class="mt-2 text-sm text-{{ $statusColor }}-700/80">Waiting for essay grading</div>
                    <div class="mt-1 text-xs text-{{ $statusColor }}-600/60">MC Score: <span
                            class="font-semibold">{{ $mcPct }}%</span></div>
                @else
                    <div class="mt-2 text-sm text-{{ $statusColor }}-700/80">Final Score: <span
                            class="font-bold text-lg">{{ $totalPct }}%</span></div>
                @endif
            @else
                <div class="mt-2 text-xs text-{{ $statusColor }}-700/80">Not attempted yet</div>
            @endif

            @if ($topAttempt && !$topUnderReview && !$topPassed && ($post['is_latest'] ?? false))
                <div class="mt-4 pt-4 border-t border-{{ $statusColor }}-200 w-full">
                    <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                        class="w-full inline-flex justify-center items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 shadow-sm transition">
                        <x-icon name="o-arrow-path" class="size-4" />
                        Retry Post-Test
                    </a>
                </div>
            @endif
        </section>

        {{-- Comparison Chart --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 md:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Score Comparison</h2>
            </div>
            @if ($topUnderReview)
                <div
                    class="h-48 flex flex-col items-center justify-center text-center text-gray-500 bg-amber-50/50 rounded-lg border border-dashed border-amber-200">
                    <x-icon name="o-clock" class="size-10 text-amber-400 mb-3" />
                    <p class="text-sm font-medium text-amber-700">Awaiting Grading</p>
                    <p class="text-xs text-amber-600/70 mt-1 max-w-xs">The comparison chart will be displayed after all
                        answers are graded.</p>
                </div>
            @elseif (!$topAttempt)
                <div
                    class="h-48 flex flex-col items-center justify-center text-center text-gray-500 bg-gray-50/50 rounded-lg border border-dashed border-gray-200">
                    <p class="text-sm font-medium text-gray-600">No data available</p>
                </div>
            @else
                <div class="h-56" x-data="{ chart: null }" x-init="(() => {
                    const ctx = $refs.chart.getContext('2d');
                    const data = {
                        labels: ['Pre-Test', 'Post-Test'],
                        datasets: [{
                            label: 'Score (%)',
                            data: [{{ $prePct }}, {{ $totalPct }}],
                            backgroundColor: ['rgba(59,130,246,0.2)', 'rgba(16,185,129,0.2)'],
                            borderColor: ['rgba(59,130,246,1)', 'rgba(16,185,129,1)'],
                            borderWidth: 2,
                            borderRadius: 6,
                            barPercentage: 0.6,
                        }]
                    };
                    const options = {
                        animation: { duration: 450 },
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y}%` } }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { stepSize: 20 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    };
                    const render = () => { chart = new window.Chart(ctx, { type: 'bar', data, options }); };
                    if (!window.Chart) {
                        const s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                        s.onload = render;
                        document.head.appendChild(s);
                    } else { render(); }
                })()">
                    <canvas x-ref="chart"></canvas>
                </div>
            @endif
        </section>
    </div>

    {{-- History List --}}
    <div class="mt-8">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Attempt History</h3>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            @if (count($attempts) === 0)
                <div class="p-4 text-center text-sm text-gray-500">No history available.</div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($attempts as $it)
                        @php $isCurrent = $it['is_current'] ?? false; @endphp
                        <li class="relative {{ $isCurrent ? '' : 'group hover:bg-gray-50' }} transition-colors">
                            @if ($isCurrent)
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary rounded-r"></div>
                            @endif

                            @if (!$isCurrent)
                                <a href="{{ route('courses-result.index', ['course' => $course->id, 'attemptId' => $it['id']]) }}"
                                    wire:navigate class="flex items-center justify-between p-4 pl-5 cursor-pointer">
                                @else
                                    <div class="flex items-center justify-between p-4 pl-5 bg-primary/5">
                            @endif

                            <div class="flex items-center gap-4">
                                <div
                                    class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $isCurrent ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $it['number'] }}
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-medium {{ $isCurrent ? 'text-primary' : 'text-gray-900' }}">
                                        Attempt #{{ $it['number'] }}
                                        @if ($isCurrent)
                                            <span class="ml-1 text-xs font-normal text-primary/70">(Active)</span>
                                        @endif
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $it['submitted_at'] }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                @if ($it['status'] === 'under_review')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <div class="size-1.5 rounded-full bg-amber-500 animate-pulse"></div> Review
                                    </span>
                                @else
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-gray-900">{{ $it['percent'] ?? 0 }}%</div>
                                        <div class="text-[10px] text-gray-500">Score</div>
                                    </div>
                                    @if ($it['passed'] ?? false)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Passed</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>
                                    @endif
                                @endif

                                @if (!$isCurrent)
                                    <x-icon name="o-chevron-right"
                                        class="size-4 text-gray-400 group-hover:text-gray-600 transition" />
                                @endif
                            </div>

                            @if (!$isCurrent)
                                </a>
                            @else
        </div>
        @endif
        </li>
        @endforeach
        </ul>
        @endif
    </div>
</div>

{{-- Detailed Breakdown --}}
@if ($topAttempt)
    <div class="mb-6 mt-8">
        <div class="flex items-center gap-2 mb-4">
            <h3 class="text-base font-semibold text-gray-900">Post-Test Details</h3>
            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">Attempt
                #{{ $currentAttemptNum }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Multiple Choice --}}
            @if ($mcTotal > 0)
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-sm font-semibold text-gray-900">Multiple Choice</h3>
                        <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-600">Auto</span>
                    </div>
                    <div class="text-xs text-gray-500 mb-4">Automatically graded by system</div>

                    <div class="flex items-center gap-6">
                        <div class="flex-1 space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Correct Answers</span>
                                <span class="font-medium text-green-600">{{ $correct }} /
                                    {{ $mcTotal }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Points Earned</span>
                                <span class="font-bold text-gray-900">{{ $mcScore }} / {{ $maxAuto }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Percentage</span>
                                <span class="font-bold text-emerald-600">{{ $mcPct }}%</span>
                            </div>
                        </div>

                        <div class="w-20 h-20 flex-shrink-0 relative">
                            <div x-data="{ chart: null }" x-init="(() => {
                                const ctx = $refs.donutMc.getContext('2d');
                                const render = () => {
                                    // Avoid double-instantiating when Livewire rehydrates
                                    if (chart) { try { chart.destroy(); } catch (e) {} }
                                    chart = new window.Chart(ctx, {
                                        type: 'doughnut',
                                        data: { datasets: [{ data: [{{ $incorrect }}, {{ $correct }}], backgroundColor: ['#F3F4F6', '#10B981'], borderWidth: 0, cutout: '70%' }] },
                                        options: { plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: { duration: 0 }, responsive: true, maintainAspectRatio: true }
                                    });
                                };
                            
                                if (!window.Chart) {
                                    const existing = document.querySelector('script[data-chartjs]');
                                    if (existing) {
                                        existing.addEventListener('load', render, { once: true });
                                    } else {
                                        const s = document.createElement('script');
                                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                                        s.setAttribute('data-chartjs', '1');
                                        s.onload = render;
                                        document.head.appendChild(s);
                                    }
                                } else {
                                    render();
                                }
                            })()">
                                <canvas x-ref="donutMc"></canvas>
                                <div
                                    class="absolute inset-0 flex items-center justify-center text-xs font-bold text-emerald-600">
                                    {{ $mcPct }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Essay --}}
            @if ($essayCount > 0)
                <div class="rounded-xl border border-gray-200 bg-white p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold text-gray-900">Essay / Short Answer</h3>
                            <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-600">Manual</span>
                        </div>
                        <div class="text-xs text-gray-500 mb-4">Manually graded by instructor</div>
                    </div>

                    @if ($essayPending)
                        <div class="flex items-center gap-3 bg-amber-50 rounded-lg p-4 border border-amber-100">
                            <div class="bg-amber-100 p-2.5 rounded-full">
                                <x-icon name="o-clock" class="size-6 text-amber-600" />
                            </div>
                            <div>
                                <div class="text-sm font-bold text-amber-800">Awaiting Grading</div>
                                <div class="text-xs text-amber-600/80">{{ $essayCount }} answers submitted</div>
                            </div>
                        </div>
                    @else
                        <div class="flex items-end justify-between">
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Points Earned</div>
                                <div class="text-3xl font-bold text-gray-900">{{ $essayScore }} <span
                                        class="text-sm font-normal text-gray-400">/ {{ $maxManual }}</span></div>
                            </div>
                            <div
                                class="bg-green-50 text-green-700 px-3 py-1.5 rounded-full text-xs font-medium border border-green-100">
                                <x-icon name="o-check" class="size-3 inline -mt-0.5" /> Graded
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
</div>
