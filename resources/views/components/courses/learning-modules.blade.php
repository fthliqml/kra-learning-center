<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold">Topics</h3>
        <x-button type="button" size="sm" icon="o-plus" wire:click="addTopic" class="border-gray-400" outline>Add
            Topic</x-button>
    </div>

    @forelse($topics as $ti => $topic)
        <div class="rounded-xl p-5 bg-base-100 shadow ring-1 ring-base-300/50 border border-gray-400 space-y-5">
            <div class="flex items-start gap-3">
                <span
                    class="inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-md bg-gradient-to-br from-secondary/20 to-secondary/10 text-secondary text-xs font-semibold shadow-inner">{{ $loop->iteration }}</span>
                <div class="flex-1 space-y-2">
                    <x-input placeholder="Topic Title" wire:model.defer="topics.{{ $ti }}.title" />
                    <div class="flex gap-2 flex-wrap">
                        <x-button type="button" size="xs" icon="o-plus"
                            wire:click="addSection({{ $ti }})" outline class="border-gray-400">Add Sub
                            Topic</x-button>
                    </div>
                </div>
                <x-button type="button" icon="o-trash" class="btn-ghost text-red-500" title="Delete Topic"
                    wire:click="removeTopic({{ $ti }})" />
            </div>

            @if (!empty($topic['sections']))
                <div
                    class="divide-y divide-base-300/60 border border-base-300/40 rounded-md bg-base-200/30 overflow-hidden">
                    @foreach ($topic['sections'] as $si => $section)
                        <div class="relative group px-4 py-4 space-y-4">
                            <span
                                class="absolute left-0 top-0 h-full w-1 bg-primary opacity-70 group-hover:opacity-90 transition"
                                aria-hidden="true"></span>
                            <div class="flex items-start gap-3">
                                <span
                                    class="inline-flex items-center justify-center w-12 h-6 shrink-0 rounded bg-primary/15 text-primary text-[10px] font-semibold tracking-wide">{{ $ti + 1 . '.' . ($si + 1) }}</span>
                                <div class="flex-1 space-y-2">
                                    <x-input placeholder="Sub Topic Title"
                                        wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.title" />
                                    <div class="flex gap-2 flex-wrap">
                                        <x-button type="button" size="xs" icon="o-plus"
                                            wire:click="addSectionResource({{ $ti }}, {{ $si }}, 'pdf')"
                                            outline class="border-gray-400">PDF</x-button>
                                        <x-button type="button" size="xs" icon="o-plus"
                                            wire:click="addSectionResource({{ $ti }}, {{ $si }}, 'youtube')"
                                            outline class="border-gray-400">YouTube</x-button>
                                        <x-button type="button" size="xs" :variant="$topics[$ti]['sections'][$si]['quiz']['enabled'] ?? false
                                            ? 'primary'
                                            : 'ghost'"
                                            wire:click="toggleSectionQuiz({{ $ti }}, {{ $si }})"
                                            icon="o-academic-cap"
                                            class="border-gray-400">{{ $topics[$ti]['sections'][$si]['quiz']['enabled'] ?? false ? 'Quiz ON' : 'Quiz OFF' }}</x-button>
                                    </div>
                                </div>
                                <x-button type="button" icon="o-trash" class="btn-ghost text-error"
                                    title="Delete Sub Topic"
                                    wire:click="removeSection({{ $ti }}, {{ $si }})" />
                            </div>

                            @if (!empty($section['resources']))
                                <div class="flex flex-col gap-2">
                                    @foreach ($section['resources'] as $ri => $res)
                                        <div
                                            class="flex items-start gap-3 rounded-md bg-base-100/70 px-3 py-2 shadow-sm ring-1 ring-base-300/40 hover:ring-base-300 transition">
                                            <div class="flex-1 space-y-2">
                                                @if (($res['type'] ?? '') === 'pdf')
                                                    <div class="space-y-1">
                                                        <label class="text-xs font-medium">PDF Module</label>
                                                        <input type="file" accept="application/pdf"
                                                            wire:model="topics.{{ $ti }}.sections.{{ $si }}.resources.{{ $ri }}.file"
                                                            class="file-input file-input-bordered file-input-sm w-full max-w-md" />
                                                        @if (isset($res['file']) && $res['file'])
                                                            <p class="text-[11px] text-success">File ready:
                                                                {{ $res['file']->getClientOriginalName() }}</p>
                                                        @endif
                                                    </div>
                                                @elseif(($res['type'] ?? '') === 'youtube')
                                                    <div class="space-y-1">
                                                        <label class="text-xs font-medium">YouTube URL</label>
                                                        <x-input placeholder="https://www.youtube.com/watch?v=..."
                                                            wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.resources.{{ $ri }}.url"
                                                            wire:change="$refresh" />
                                                        @php($yt = $res['url'] ?? '')
                                                        @if ($yt && preg_match('/v=([\w-]+)/', $yt, $m))
                                                            <iframe
                                                                class="w-full max-w-md aspect-video rounded-md ring-1 ring-base-300/50"
                                                                src="https://www.youtube.com/embed/{{ $m[1] }}"
                                                                allowfullscreen></iframe>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            <x-button type="button" icon="o-x-mark" class="btn-ghost text-error"
                                                title="Delete"
                                                wire:click="removeSectionResource({{ $ti }}, {{ $si }}, {{ $ri }})" />
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-[11px] italic text-base-content/50">No resources added yet.</p>
                            @endif

                            @if ($section['quiz']['enabled'] ?? false)
                                <div class="mt-1 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[11px] font-semibold tracking-wide uppercase text-base-content/70">Quiz
                                            Questions</span>
                                        <x-button type="button" size="xs" icon="o-plus"
                                            wire:click="addSectionQuizQuestion({{ $ti }}, {{ $si }})"
                                            outline class="border-gray-400">Add Question</x-button>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        @forelse ($section['quiz']['questions'] ?? [] as $qi => $qq)
                                            <div class="relative rounded-md p-3 bg-base-100/70 ring-1 ring-base-300/40">
                                                <span
                                                    class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-info/60 to-info/10"></span>
                                                <div class="flex items-center gap-2 mb-2">
                                                    <x-select :options="[
                                                        ['value' => 'multiple', 'label' => 'Multiple'],
                                                        ['value' => 'essay', 'label' => 'Essay'],
                                                    ]" option-value="value"
                                                        option-label="label" class="w-40"
                                                        wire:model="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.type"
                                                        wire:click="$refresh" />
                                                    <x-input class="flex-1" placeholder="Write the question"
                                                        wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.question" />
                                                    <x-button type="button" icon="o-trash"
                                                        class="btn-ghost text-error" title="Delete Question"
                                                        wire:click="removeSectionQuizQuestion({{ $ti }}, {{ $si }}, {{ $qi }})" />
                                                </div>
                                                @if (($qq['type'] ?? '') === 'multiple')
                                                    <div class="space-y-2">
                                                        @foreach ($qq['options'] ?? [] as $oi => $opt)
                                                            <div class="flex items-center gap-2">
                                                                <x-input class="flex-1" placeholder="Option"
                                                                    wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.options.{{ $oi }}" />
                                                                <x-button type="button" icon="o-x-mark"
                                                                    class="btn-ghost text-error" title="Remove Option"
                                                                    wire:click="removeSectionQuizOption({{ $ti }}, {{ $si }}, {{ $qi }}, {{ $oi }})" />
                                                            </div>
                                                        @endforeach
                                                        <x-button type="button" size="xs" icon="o-plus"
                                                            outline
                                                            wire:click="addSectionQuizOption({{ $ti }}, {{ $si }}, {{ $qi }})"
                                                            class="border-gray-400">Add Option</x-button>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-[11px] italic text-base-content/50">No questions yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs italic text-base-content/50">No sub topics yet.</p>
            @endif
        </div>
    @empty
        <div class="text-xs text-gray-400 italic">No topics have been added yet.</div>
    @endforelse

    <div class="flex items-center justify-between pt-4">
        <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goBack"
            title="Go to previous step">
            <x-icon name="o-arrow-left" class="size-4" />
            <span>Back</span>
        </x-ui.button>
        <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNext" title="Go to next step">
            <span>Next</span>
            <x-icon name="o-arrow-right" class="size-4" />
        </x-ui.button>
    </div>
</div>
