@php
    $questions = $questions ?? collect();
@endphp

<div x-data="posttestForm($wire)" x-init="init()" class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Posttest</h1>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 md:p-6 shadow-sm mb-5 md:mb-6" x-data="{ open: true }">
        <button type="button"
            class="md:hidden inline-flex items-center gap-2 text-xs font-medium text-gray-600 transition"
            @click="open = !open">
            <span x-text="open ? 'Sembunyikan Instruksi' : 'Tampilkan Instruksi'"></span>
            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <div class="flex items-start gap-4 mt-5 md:mt-0" x-show="open" x-transition>
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary">
                <x-icon name="o-clipboard-document-check" class="size-5" />
            </div>
            <div class="flex-1">
                <h2 class="text-base font-semibold text-gray-900">Sebelum Memulai</h2>
                <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                    Posttest ini mengukur pemahaman Anda setelah menyelesaikan materi.
                </p>
                <ul class="mt-3 text-xs text-gray-500 grid gap-1 grid-cols-1">
                    <li class="inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary/60"></span>
                        Jumlah Soal:
                        <strong>{{ $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions) }}</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <form x-ref="formEl" @submit.prevent="submit" class="space-y-4 md:space-y-5"
        x-bind:aria-busy="submitting ? 'true' : 'false'">
        @forelse ($questions as $index => $q)
            <fieldset class="rounded-lg border border-gray-200 bg-white p-4 md:p-5 shadow-sm relative"
                :class="errors['{{ $q['id'] }}'] ? 'border-red-300 ring-1 ring-red-200' : ''">
                <legend class="sr-only">Soal {{ $index + 1 }}</legend>
                <div
                    class="absolute top-4 left-4 md:top-5 md:left-5 inline-flex items-center justify-center rounded-md bg-primary/10 text-primary text-[11px] font-semibold px-2 py-0.5 h-5 min-w-[28px]">
                    {{ $index + 1 }}
                </div>
                <div class="pl-10 pr-0 md:pr-4 mb-5">
                    <p class="text-sm font-medium text-gray-800 leading-snug">{{ $q['text'] }}</p>
                </div>
                @if ($q['type'] === 'essay')
                    <div class="space-y-2">
                        <textarea name="{{ $q['id'] }}" x-ref="txt_{{ $q['id'] }}" rows="4"
                            placeholder="Tulis jawaban Anda di sini..."
                            class="w-full rounded-md border border-gray-300 focus:border-primary focus:ring-primary/30 text-sm text-gray-800 placeholder:text-gray-400 resize-y p-3"
                            :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                            @input="answers['{{ $q['id'] }}']=$event.target.value; if($event.target.value.trim().length){ delete errors['{{ $q['id'] }}'] }"></textarea>
                        <div class="flex justify-between text-[11px] text-gray-400" x-data="{ limit: 2000 }">
                            <span x-text="(answers['{{ $q['id'] }}']||'').length + ' karakter'"></span>
                            <span>Batas saran 2000</span>
                        </div>
                    </div>
                @else
                    <div class="grid gap-1.5 md:gap-2">
                        @foreach ($q['options'] as $optIndex => $opt)
                            <label
                                class="flex items-start gap-3 group cursor-pointer rounded-md px-1.5 py-1 hover:bg-gray-50">
                                <input type="radio" name="{{ $q['id'] }}"
                                    class="mt-1 h-4 w-4 text-primary focus:ring-primary/40 border-gray-300 rounded"
                                    :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                                    value="{{ is_array($opt) ? $opt['id'] : $opt }}"
                                    @change="answers['{{ $q['id'] }}']= '{{ is_array($opt) ? $opt['id'] : addslashes($opt) }}'; delete errors['{{ $q['id'] }}']">
                                <span class="text-sm text-gray-700 group-hover:text-gray-900 leading-snug">
                                    {{ is_array($opt) ? $opt['text'] : $opt }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <template x-if="errors['{{ $q['id'] }}']">
                    <p class="mt-3 text-xs text-red-600 flex items-center gap-1">
                        <x-icon name="o-exclamation-triangle" class="size-4" />
                        <span x-text="errors['{{ $q['id'] }}']"></span>
                    </p>
                </template>
            </fieldset>
        @empty
            <div class="p-6 border border-dashed rounded-lg text-center text-sm text-gray-500 bg-white">
                Belum ada soal posttest untuk course ini.
            </div>
        @endforelse

        @php $qCount = $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions); @endphp
        @if ($qCount > 0)
            <div class="pt-2 flex flex-row items-center justify-between md:justify-end gap-3">
                <button type="button" @click="resetForm" :disabled="submitting"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-gray-100 text-gray-700 px-4 py-2.5 text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300/50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <x-icon name="o-arrow-path" class="size-4" />
                    <span>Reset</span>
                </button>
                <button type="submit" :disabled="submitting"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary text-white px-5 py-2.5 text-sm font-medium shadow hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <x-icon name="o-paper-airplane" class="size-4" />
                    <span>Submit</span>
                </button>
            </div>
        @endif
    </form>
</div>

<script>
    function posttestForm(wire) {
        return {
            lw: wire,
            answers: {},
            errors: {},
            submitted: false,
            submitting: false,
            totalQuestions: {{ $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions) }},
            get answeredCount() {
                return Object.keys(this.answers).length;
            },
            progressPercent() {
                return this.totalQuestions ? (this.answeredCount / this.totalQuestions) * 100 : 0;
            },
            key() {
                return `posttest:${@json($userId)}:${@json($posttestId)}`;
            },
            init() {
                try {
                    const raw = localStorage.getItem(this.key());
                    if (raw) {
                        const parsed = JSON.parse(raw);
                        if (parsed && typeof parsed === 'object') {
                            this.answers = parsed;
                            Object.entries(this.answers).forEach(([name, val]) => {
                                const el = this.$refs.formEl?.querySelector(
                                    `input[type=radio][name='${name}'][value='${val}']`);
                                if (el) el.checked = true;
                                const ta = this.$refs[`txt_${name}`];
                                if (ta && ta.value !== undefined && typeof val === 'string') ta.value = val;
                            });
                        }
                    }
                } catch (e) {}
                this.$nextTick(() => {
                    this.$refs.formEl?.addEventListener('input', this.saveDraft.bind(this));
                    this.$refs.formEl?.addEventListener('change', this.saveDraft.bind(this));
                });
                window.addEventListener('beforeunload', this.saveDraft.bind(this));
            },
            saveDraft() {
                try {
                    localStorage.setItem(this.key(), JSON.stringify(this.answers));
                } catch (e) {}
            },
            validate() {
                this.errors = {};
                const required = @json(
                    ($questions instanceof \Illuminate\Support\Collection
                        ? $questions->pluck('id')
                        : collect($questions)->pluck('id')
                    )->values());
                required.forEach(id => {
                    if (!this.answers[id]) {
                        this.errors[id] = 'Harus dipilih.';
                    }
                });
                return Object.keys(this.errors).length === 0;
            },
            resetForm() {
                if (this.$refs.formEl) this.$refs.formEl.reset();
                this.answers = {};
                this.errors = {};
                this.submitted = false;
                this.saveDraft();
            },
            submit() {
                if (!this.validate()) return;
                this.submitting = true;
                this.submitted = true;
                this.saveDraft();
                Promise.resolve(this.lw.submitPosttest(this.answers))
                    .catch(() => {
                        this.submitting = false;
                    })
                    .finally(() => {
                        this.submitting = false;
                    });
            }
        }
    }
</script>
