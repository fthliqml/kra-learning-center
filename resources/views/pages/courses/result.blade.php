@php
    // Calculate percentages and delta
    $prePct = (int) ($pre['percent'] ?? 0 ?: 0);
    $postPct = (int) ($post['percent'] ?? 0 ?: 0);
    $delta = $postPct - $prePct;

    // Determine top attempt status
    $topAttempt = $post['attempt'] ?? null;
    $topUnderReview = false;
    $topPassed = false;
    if ($topAttempt) {
        $topUnderReview = $topAttempt->status === \App\Models\TestAttempt::STATUS_UNDER_REVIEW;
        // Derive pass: is_passed OR 100% OR meets passing threshold
        $passing = (int) ($post['passing'] ?? 0);
        $derivedPass = false;
        if (!$topUnderReview) {
            if ($postPct === 100) {
                $derivedPass = true;
            } elseif ($passing > 0 && $postPct >= $passing) {
                $derivedPass = true;
            } elseif ((bool) $topAttempt->is_passed) {
                $derivedPass = true;
            }
        }
        $topPassed = $derivedPass;
    }

    // Posttest details
    $mcTotal = (int) ($post['mc_total'] ?? 0);
    $correct = (int) ($post['correct'] ?? 0);
    $incorrect = max(0, $mcTotal - $correct);
    $attempts = $post['attempts'] ?? [];
@endphp

<div class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative">
    {{-- Heading --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Result</h1>
    </div>

    {{-- Posttest Status --}}
    @if ($topAttempt)
        @if ($topUnderReview)
            <div class="rounded-md border border-amber-200 bg-amber-50 text-amber-800 p-3 mb-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-clock" class="size-5" />
                        <p class="text-sm">Posttest dalam proses penilaian. Harap menunggu review.</p>
                    </div>
                </div>
            </div>
        @elseif ($topPassed)
            <div class="rounded-md border border-green-200 bg-green-50 text-green-800 p-3 mb-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-check-circle" class="size-5" />
                        <p class="text-sm font-semibold">Lulus</p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-md border border-red-200 bg-red-50 text-red-800 p-3 mb-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-x-circle" class="size-5" />
                        <p class="text-sm font-semibold">Tidak Lulus</p>
                    </div>
                    <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1.5 rounded-md bg-primary text-white hover:bg-primary/90">Coba
                        Lagi</a>
                </div>
            </div>
        @endif
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 mb-6 md:mb-6">
        {{-- Grafik Perbandingan --}}
        <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-6 md:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-gray-900">Perbandingan Nilai</h2>
            </div>
            <div x-data="{ pre: {{ $prePct }}, post: {{ $postPct }}, chart: null }" x-init="(() => {
                const ctx = $refs.chart.getContext('2d');
                const data = {
                    labels: ['Pretest', 'Posttest'],
                    datasets: [{
                        label: 'Persentase',
                        data: [{{ $prePct }}, {{ $postPct }}],
                        backgroundColor: ['rgba(59,130,246,0.2)', 'rgba(16,185,129,0.2)'],
                        borderColor: ['rgba(59,130,246,1)', 'rgba(16,185,129,1)'],
                        borderWidth: 2,
                        borderRadius: 6,
                        maxBarThickness: 48,
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
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100,
                            ticks: { stepSize: 20, callback: (v) => `${v}%` },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                        x: { grid: { display: false } }
                    }
                };
                const render = () => { chart = new window.Chart(ctx, { type: 'bar', data, options }); };
                if (!window.Chart) {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                    s.onload = render;
                    document.head.appendChild(s);
                } else {
                    render();
                }
            })()">
                <div class="h-64 md:h-50">
                    <canvas x-ref="chart" aria-label="Grafik perbandingan nilai pretest dan posttest"
                        role="img"></canvas>
                </div>
            </div>
        </section>

        {{-- Ringkasan --}}
        <div class="md:col-span-1">
            <div class="flex flex-row md:flex-col gap-3 md:gap-4 overflow-x-auto md:overflow-visible -mx-2 px-2 ">
                <div class="rounded-xl border border-gray-200 bg-white p-4 min-w-[100px]">
                    <div class="text-[11px] font-medium text-gray-500">Pretest</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $prePct }}%</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 min-w-[100px]">
                    <div class="text-[11px] font-medium text-gray-500">Posttest</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $postPct }}%</div>
                </div>
                <div
                    class="rounded-xl border border-{{ $delta >= 0 ? 'green' : 'red' }}-200 bg-{{ $delta >= 0 ? 'green' : 'red' }}-50 p-4 min-w-[100px]">
                    <div class="text-[11px] font-medium text-{{ $delta >= 0 ? 'green' : 'red' }}-700">Perubahan</div>
                    <div class="mt-1 text-2xl font-bold text-{{ $delta >= 0 ? 'green' : 'red' }}-800">
                        {{ $delta >= 0 ? '+' : '' }}{{ $delta }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($mcTotal > 0)
        <div class="mt-6 flex flex-col md:flex-row md:items-start gap-4">
            {{-- Hasil Posttest --}}
            <div class="rounded-lg border border-gray-200 p-4 md:w-1/2">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-semibold text-gray-900">Hasil Posttest</h3>
                    <div class="text-xs text-gray-500">Multiple Choice</div>
                </div>
                <div x-data="{ chart: null }" x-init="(() => {
                    const ctx = $refs.donut.getContext('2d');
                    const data = {
                        labels: ['Benar', 'Salah'],
                        datasets: [{
                            data: [{{ $correct }}, {{ $incorrect }}],
                            backgroundColor: ['rgba(16,185,129,0.7)', 'rgba(239,68,68,0.25)'],
                            borderColor: ['rgba(16,185,129,1)', 'rgba(239,68,68,0.4)'],
                            borderWidth: 1,
                        }]
                    };
                    const options = {
                        cutout: '65%',
                        plugins: { legend: { display: true, position: 'bottom' } },
                        animation: { duration: 450 },
                        responsive: true,
                        maintainAspectRatio: false,
                    };
                    const render = () => { chart = new window.Chart(ctx, { type: 'doughnut', data, options }); };
                    if (!window.Chart) {
                        const s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                        s.onload = render;
                        document.head.appendChild(s);
                    } else {
                        render();
                    }
                })()">
                    <div class="h-52">
                        <canvas x-ref="donut" aria-label="Donut jawaban benar vs salah" role="img"></canvas>
                    </div>
                    <div class="mt-5 text-sm text-gray-600 text-center">
                        <span class="font-semibold text-gray-900">{{ $correct }}</span> benar dari
                        <span class="font-semibold text-gray-900">{{ $mcTotal }}</span> soal pilihan ganda
                    </div>
                </div>
            </div>

            {{-- Riwayat Percobaan --}}
            <div class="rounded-lg border border-gray-200 p-4 md:w-1/2">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-900">Riwayat Percobaan</h3>
                </div>
                @if (count($attempts) === 0)
                    <p class="text-sm text-gray-500">Belum ada riwayat percobaan.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($attempts as $it)
                            <li class="py-2 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900">Percobaan #{{ $it['number'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $it['submitted_at'] }}</div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-xs text-gray-500">{{ $it['auto'] }} / {{ $it['total'] }}</div>
                                    <div class="text-sm tabular-nums text-gray-900">
                                        {{ $it['percent'] !== null ? $it['percent'] . '%' : '-' }}</div>
                                    @if ($it['status'] === 'under_review')
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-amber-50 text-amber-700 border border-amber-200">Review</span>
                                    @elseif ($it['passed'])
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-green-50 text-green-700 border border-green-200">Lulus</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-red-50 text-red-700 border border-red-200">Tidak
                                            Lulus</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</div>
