@php
    // $questions is provided by Livewire component; ensure it's a collection/array
    $questions = $questions ?? collect();
@endphp

<div x-data="pretestForm()" x-init="init()" class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Pretest</h1>
        </div>
    </div>

    <!-- Intro / Instructions -->
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
                    Pretest ini membantu kami memahami titik awal pengetahuan Anda sehingga pembelajaran bisa lebih
                    relevan.
                    Jawablah sejujur mungkin. Hasil pretest tidak menurunkan progres Anda.
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

    <!-- Questions -->
    <form @submit.prevent="submit" class="space-y-4 md:space-y-5">
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
                <template x-if="errors['{{ $q['id'] }}']">
                    <p class="mt-3 text-xs text-red-600 flex items-center gap-1">
                        <x-icon name="o-exclamation-triangle" class="size-4" />
                        <span x-text="errors['{{ $q['id'] }}']"></span>
                    </p>
                </template>
            </fieldset>
        @empty
            <div class="p-6 border border-dashed rounded-lg text-center text-sm text-gray-500 bg-white">
                Belum ada soal pretest untuk course ini.
            </div>
        @endforelse

        <!-- Actions -->
        <div class="pt-2 flex flex-row items-center justify-between md:justify-end gap-3">
            <button type="button" @click="resetForm"
                class="inline-flex items-center justify-center gap-2 rounded-md bg-gray-100 text-gray-700 px-4 py-2.5 text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300/50 transition">
                <x-icon name="o-arrow-path" class="size-4" />
                <span>Reset</span>
            </button>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-md bg-primary text-white px-5 py-2.5 text-sm font-medium shadow hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 transition">
                <x-icon name="o-paper-airplane" class="size-4" />
                <span>Submit</span>
            </button>
        </div>
    </form>
</div>

<script>
    function pretestForm() {
        return {
            answers: {},
            errors: {},
            submitted: false,
            totalQuestions: {{ $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions) }},
            get answeredCount() {
                return Object.keys(this.answers).length;
            },
            progressPercent() {
                return this.totalQuestions ? (this.answeredCount / this.totalQuestions) * 100 : 0;
            },
            init() {},
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
                this.answers = {};
                this.errors = {};
                this.submitted = false;
            },
            submit() {
                if (!this.validate()) {
                    return;
                }
                this.submitted = true;
                // Placeholder: emit Livewire event if needed
                window.dispatchEvent(new CustomEvent('pretest-submitted', {
                    detail: {
                        answers: this.answers
                    }
                }));
                // Simple UX feedback
                alert('Pretest terkirim. Terima kasih!');
            }
        }
    }
</script>
