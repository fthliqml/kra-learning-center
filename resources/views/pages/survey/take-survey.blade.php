<div x-data="{ showConfirm: false }">
    <div class="w-full flex items-center gap-2 mb-5">
        <a href="javascript:history.back()"
            class="inline-flex items-center px-2 py-1 rounded hover:bg-primary/10 text-primary text-sm font-medium focus:outline-none">
            <x-icon name="o-arrow-left" class="w-5 h-5 mr-1" />
            Back
        </a>
        <div class="flex gap-2 items-center">
            <h1 class="flex-1 text-primary text-2xl font-bold text-center lg:text-start">
                {{ $trainingName }}
            </h1>
            <span class="badge badge-primary badge-soft">Level
                {{ $surveyLevel }}</span>
        </div>
    </div>

    <form x-ref="formEl" class="space-y-4 md:space-y-5" x-bind:aria-busy="submitting ? 'true' : 'false'">
        @forelse ($questions as $index => $q)
            <fieldset
                class="relative rounded-lg border bg-white p-4 md:p-5 shadow-sm {{ in_array($index, $errorQuestionIndexes ?? []) ? 'border-red-500 ring-1 ring-red-300' : 'border-gray-200' }}">
                <legend class="sr-only">Soal {{ $index + 1 }}</legend>

                {{-- Nomor Soal --}}
                <div
                    class="absolute top-4 left-4 md:top-5 md:left-5 inline-flex items-center justify-center rounded-md bg-primary/10 text-primary text-[11px] font-semibold px-2 py-0.5 h-5 min-w-[28px]">
                    {{ $index + 1 }}
                </div>

                {{-- Teks Pertanyaan --}}
                <div class="pl-10 mb-3">
                    <p class="text-base font-medium text-gray-900 leading-snug">
                        {{ $q->text }}
                    </p>
                </div>

                {{-- Opsi atau Essay --}}
                @if ($q->question_type === 'multiple')
                    <div class="space-y-2">
                        @foreach ($q->options as $option)
                            <label
                                class="flex items-center gap-2 p-2 border border-gray-300 rounded-lg cursor-pointer transition-all duration-200 hover:bg-primary/5 hover:border-primary/40">
                                <input type="radio" name="question_{{ $q->id }}" value="{{ $option->id }}"
                                    class="radio radio-sm radio-primary"
                                    wire:model.defer="answers.{{ $q->id }}" />
                                <span class="text-sm text-base-content/90">{{ $option->text }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <textarea name="question_{{ $q->id }}" rows="3"
                        class="w-full border border-gray-200 rounded-md focus:ring-2 focus:ring-primary focus:border-primary outline-none p-2 text-sm"
                        wire:model.defer="answers.{{ $q->id }}"></textarea>
                @endif

                {{-- Error Message --}}
                <template x-if="errors['{{ $q->id }}']">
                    <p class="mt-2 text-xs text-red-600 flex items-center gap-1">
                        <x-icon name="o-exclamation-triangle" class="size-4" />
                        <span x-text="errors['{{ $q->id }}']"></span>
                    </p>
                </template>
            </fieldset>


        @empty
            <div class="p-6 border border-dashed rounded-lg text-center text-sm text-gray-500 bg-white">
                Belum ada soal pretest untuk course ini.
            </div>
        @endforelse

    </form>


    <div class="w-full flex justify-end mt-5">
        <x-ui.button @click.prevent="showConfirm = true" variant="primary">Submit</x-ui.button>
    </div>

    <!-- Modal Confirmation -->
    <div x-show="showConfirm" style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-2">Submit Survey?</h2>
            <p class="mb-4 text-sm text-gray-600">Are you sure you want to submit your answers? You won't be able to
                change them after submission.</p>
            <div class="flex justify-end gap-2">
                <button type="button"
                    class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 hover:cursor-pointer"
                    @click="showConfirm = false">Cancel</button>
                <button type="button"
                    class="px-4 py-2 rounded bg-primary text-white hover:bg-primary/90 hover:cursor-pointer"
                    @click="showConfirm = false; $wire.confirmAndSubmit()">Yes, Submit</button>
            </div>
        </div>
    </div>

    <x-loading-overlay text="Submitting..." target="confirmAndSubmit" />

</div>
