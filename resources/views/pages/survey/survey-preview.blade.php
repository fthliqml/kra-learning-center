<div>
    <div class="w-full flex items-center gap-2 mb-2">
        <a href="javascript:history.back()"
            class="inline-flex items-center px-2 py-1 rounded hover:bg-primary/10 text-primary text-sm font-medium focus:outline-none">
            <x-icon name="o-arrow-left" class="w-5 h-5 mr-1" />
            Back
        </a>
        <h1 class="flex-1 text-primary text-2xl font-bold text-center lg:text-start">
            Preview Survey - {{ $trainingName }} (Level {{ $surveyLevel }})
        </h1>
    </div>


    <div class="space-y-4 md:space-y-5">
        @forelse ($questions as $index => $q)
            <fieldset class="relative rounded-lg border bg-white p-4 md:p-5 shadow-sm border-gray-200">
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

                {{-- Jawaban yang dipilih --}}
                @if ($q->question_type === 'multiple')
                    @php
                        $selectedOptionId = $answers[$q->id] ?? null;
                        $selectedOption = $q->options->firstWhere('id', $selectedOptionId);
                    @endphp
                    <div class="pl-10">
                        <span class="block text-xs text-gray-500 mb-1">Answer:</span>
                        @if ($selectedOption)
                            <span
                                class="inline-block px-3 py-1 rounded bg-primary/10 text-primary font-semibold text-sm">
                                {{ $selectedOption->text }}
                            </span>
                        @else
                            <span class="text-gray-400 italic text-sm">Not Answered</span>
                        @endif
                    </div>
                @else
                    <div class="pl-10">
                        <span class="block text-xs text-gray-500 mb-1">Answer:</span>
                    </div>
                    @php $essay = $answers[$q->id] ?? null; @endphp
                    @if ($essay)
                        <div
                            class="ml-10 border border-gray-200 rounded-md bg-gray-50 p-2 text-sm text-gray-800 text-left flex items-start">
                            <span class="whitespace-pre-line">{{ $essay }}</span>
                        </div>
                    @else
                        <div class="ml-10 text-gray-400 italic text-sm text-left">Not Answered</div>
                    @endif
                @endif
            </fieldset>
        @empty
            <div class="p-6 border border-dashed rounded-lg text-center text-sm text-gray-500 bg-white">
                Belum ada soal pretest untuk course ini.
            </div>
        @endforelse
    </div>

</div>
