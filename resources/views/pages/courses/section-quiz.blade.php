@php
    $questions = $questions ?? collect();
@endphp

<div x-data="sectionQuizForm($wire)" x-init="init()" class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Quiz: {{ $section->title }}</h1>
            <p class="text-xs text-gray-500">Selesaikan kuis ini untuk melanjutkan pembelajaran.</p>
        </div>
    </div>

    <form x-ref="formEl" @submit.prevent="submit" :aria-busy="submitting ? 'true' : 'false'"
        class="space-y-4 md:space-y-5">
        @forelse ($questions as $index => $q)
            <fieldset class="rounded-lg border border-gray-200 bg-white p-4 md:p-5 shadow-sm relative">
                <legend class="sr-only">Soal {{ $index + 1 }}</legend>
                <div
                    class="absolute top-4 left-4 md:top-5 md:left-5 inline-flex items-center justify-center rounded-md bg-primary/10 text-primary text-[11px] font-semibold px-2 py-0.5 h-5 min-w-[28px]">
                    {{ $index + 1 }}</div>
                <div class="pl-10 pr-0 md:pr-4 mb-5">
                    <p class="text-sm font-medium text-gray-800 leading-snug">{{ $q['text'] }}</p>
                </div>
                @if (strtolower($q['type']) === 'essay')
                    <div class="space-y-2">
                        <textarea name="{{ $q['id'] }}" x-ref="txt_{{ $q['id'] }}" rows="4"
                            placeholder="Tulis jawaban Anda di sini..."
                            class="w-full rounded-md border border-gray-300 focus:border-primary focus:ring-primary/30 text-sm text-gray-800 placeholder:text-gray-400 resize-y p-3"
                            :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                            @input="answers['{{ $q['id'] }}']=$event.target.value; if($event.target.value.trim().length){ delete errors['{{ $q['id'] }}'] }"></textarea>
                        <template x-if="errors['{{ $q['id'] }}']">
                            <p class="text-xs text-red-600">Wajib diisi.</p>
                        </template>
                    </div>
                @else
                    <div class="grid gap-1.5 md:gap-2">
                        @foreach ($q['options'] as $opt)
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
            </fieldset>
        @empty
            <div class="p-6 border border-dashed rounded-lg text-center text-sm text-gray-500 bg-white">
                Belum ada soal quiz untuk materi ini.
            </div>
        @endforelse

        @php $qCount = $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions); @endphp
        @if ($qCount > 0)
            <div class="pt-2 flex flex-row items-center justify-end gap-3">
                <button type="submit" :disabled="submitting"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary text-white px-5 py-2.5 text-sm font-medium shadow hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span class="inline-flex items-center gap-2" x-show="!submitting">
                        <x-icon name="o-paper-airplane" class="size-4" />
                        <span>Submit</span>
                    </span>
                    <span class="inline-flex items-center gap-2" x-show="submitting">
                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                        <span>Mengirim...</span>
                    </span>
                </button>
            </div>
        @endif
    </form>
</div>

<script>
    function sectionQuizForm(wire) {
        return {
            lw: wire,
            answers: {},
            errors: {},
            submitting: false,
            init() {
                this.$nextTick(() => {
                    this.$refs.formEl?.addEventListener('input', this.saveDraft?.bind?.(this));
                    this.$refs.formEl?.addEventListener('change', this.saveDraft?.bind?.(this));
                });
            },
            validate() {
                this.errors = {};
                const required = @json(
                    ($questions instanceof \Illuminate\Support\Collection
                        ? $questions->pluck('id')
                        : collect($questions)->pluck('id')
                    )->values());
                required.forEach(id => {
                    if (!this.answers[id]) this.errors[id] = 'Wajib diisi.';
                });
                return Object.keys(this.errors).length === 0;
            },
            submit() {
                if (!this.validate()) return;
                this.submitting = true;
                Promise.resolve(this.lw.submitQuiz(this.answers))
                    .catch(() => {
                        this.submitting = false;
                    });
            }
        }
    }
</script>
