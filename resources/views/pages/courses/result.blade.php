<div class="mx-auto max-w-5xl">
    <div class="flex items-center justify-between mb-5 md:mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Hasil Belajar</h1>
    </div>

    <div class="grid gap-5 md:gap-6 grid-cols-1 md:grid-cols-2">
        <!-- Pretest Summary -->
        <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-gray-900">Pretest</h2>
                @if ($pre['attempt'] ?? null)
                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border"
                        :class="''">
                        Attempt #{{ $pre['attempt']->attempt_number }}
                    </span>
                @endif
            </div>
            @if (!($pre['attempt'] ?? null))
                <p class="text-sm text-gray-500">Belum ada attempt pretest.</p>
            @else
                @php
                    $a = $pre['attempt'];
                    $max = (int) ($pre['max_auto'] ?? 0);
                    $percent = $pre['percent'] ?? null;
                    $preUnderReview = $a->status === \App\Models\TestAttempt::STATUS_UNDER_REVIEW;
                @endphp
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Skor Otomatis</dt>
                        <dd class="font-semibold text-gray-900">{{ $a->auto_score }}{{ $max ? ' / ' . $max : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Skor Total</dt>
                        <dd class="font-semibold text-gray-900">{{ $a->total_score }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="font-semibold">
                            @if ($preUnderReview)
                                <span class="text-amber-600">Menunggu review</span>
                            @else
                                <span class="text-gray-900">Selesai</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Persentase</dt>
                        <dd class="font-semibold text-gray-900">{{ $percent !== null ? $percent . '%' : '-' }}</dd>
                    </div>
                </dl>
            @endif
        </section>

        <!-- Posttest Summary -->
        <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-gray-900">Posttest</h2>
                @if ($post['attempt'] ?? null)
                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border">
                        Attempt #{{ $post['attempt']->attempt_number }}
                    </span>
                @endif
            </div>
            @if (!($post['attempt'] ?? null))
                <p class="text-sm text-gray-500">Belum ada attempt posttest.</p>
                <div class="mt-3">
                    <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                        class="inline-flex items-center gap-2 text-sm font-medium px-3 py-2 rounded-md bg-primary text-white hover:bg-primary/90">Mulai
                        Posttest</a>
                </div>
            @else
                @php
                    $a = $post['attempt'];
                    $max = (int) ($post['max_auto'] ?? 0);
                    $percent = $post['percent'] ?? null;
                    $passing = (int) ($post['passing'] ?? 0);
                    $underReview = $a->status === \App\Models\TestAttempt::STATUS_UNDER_REVIEW;
                    $passed = !$underReview && (bool) $a->is_passed;
                @endphp
                @if ($underReview)
                    <div class="rounded-md border border-amber-200 bg-amber-50 text-amber-800 p-3 mb-3">
                        <div class="flex items-center gap-2">
                            <x-icon name="o-clock" class="size-5" />
                            <p class="text-sm">Posttest dalam proses penilaian. Harap menunggu review.</p>
                        </div>
                    </div>
                @else
                    <div
                        class="rounded-md border {{ $passed ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }} p-3 mb-3">
                        <div class="flex items-center gap-2">
                            @if ($passed)
                                <x-icon name="o-check-circle" class="size-5" />
                                <p class="text-sm font-semibold">Lulus</p>
                            @else
                                <x-icon name="o-x-circle" class="size-5" />
                                <p class="text-sm font-semibold">Tidak Lulus</p>
                            @endif
                        </div>
                    </div>
                @endif

                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Skor Otomatis</dt>
                        <dd class="font-semibold text-gray-900">{{ $a->auto_score }}{{ $max ? ' / ' . $max : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Skor Total</dt>
                        <dd class="font-semibold text-gray-900">{{ $a->total_score }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Persentase</dt>
                        <dd class="font-semibold text-gray-900">{{ $percent !== null ? $percent . '%' : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Passing Score</dt>
                        <dd class="font-semibold text-gray-900">{{ $passing ?: '-' }}%</dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-center gap-3">
                    @if (!$underReview && !$passed)
                        <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                            class="inline-flex items-center gap-2 text-sm font-medium px-3 py-2 rounded-md bg-primary text-white hover:bg-primary/90">
                            Coba Lagi
                        </a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>
