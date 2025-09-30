<div class="space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold">Section</h3>
        <x-button type="button" size="sm" icon="o-plus" wire:click="addTopic" class="border-gray-400" outline>Add
            Section</x-button>
    </div>

    @forelse($topics as $ti=>$topic)
        <div class="border rounded-xl p-4 bg-base-100 space-y-4">
            <div class="flex items-start gap-3">
                <span
                    class="inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-full bg-secondary/10 text-secondary text-xs font-semibold">{{ $loop->iteration }}</span>
                <div class="flex-1 space-y-2">
                    <x-input placeholder="Judul Topik" wire:model.defer="topics.{{ $ti }}.title" />
                    <div class="flex gap-2 flex-wrap">
                        <x-button type="button" size="xs" icon="o-plus"
                            wire:click="addTopicResource({{ $ti }}, 'pdf')" outline
                            class="border-gray-400">PDF</x-button>
                        <x-button type="button" size="xs" icon="o-plus"
                            wire:click="addTopicResource({{ $ti }}, 'youtube')" outline
                            class="border-gray-400">YouTube</x-button>
                        <x-button type="button" size="xs" :variant="$topics[$ti]['quiz']['enabled'] ?? false ? 'primary' : 'ghost'"
                            wire:click="toggleTopicQuiz({{ $ti }})" icon="o-academic-cap"
                            class="border-gray-400">
                            {{ $topics[$ti]['quiz']['enabled'] ?? false ? 'Quiz ON' : 'Quiz OFF' }}
                        </x-button>
                    </div>
                </div>
                <x-button type="button" icon="o-trash" class="btn-ghost text-red-500" title="Hapus Topik"
                    wire:click="removeTopic({{ $ti }})" />
            </div>

            @if (!empty($topic['resources']))
                <div class="space-y-3">
                    @foreach ($topic['resources'] ?? [] as $ri => $res)
                        <div class="flex items-start gap-3 border rounded-lg p-3 relative">
                            <div class="flex-1 space-y-2">
                                @if (($res['type'] ?? '') === 'pdf')
                                    <div class="space-y-1">
                                        <label class="text-xs font-medium">PDF Module</label>
                                        <input type="file" accept="application/pdf"
                                            wire:model="topics.{{ $ti }}.resources.{{ $ri }}.file"
                                            class="file-input file-input-bordered w-full max-w-md" />
                                        @if (isset($res['file']) && $res['file'])
                                            <p class="text-xs text-success">File ready:
                                                {{ $res['file']->getClientOriginalName() }}</p>
                                        @endif
                                    </div>
                                @elseif(($res['type'] ?? '') === 'youtube')
                                    <div class="space-y-1">
                                        <label class="text-xs font-medium">YouTube URL</label>
                                        <x-input placeholder="https://www.youtube.com/watch?v=..."
                                            wire:model.defer="topics.{{ $ti }}.resources.{{ $ri }}.url"
                                            wire:change="$refresh" />
                                        @php($yt = $res['url'] ?? '')
                                        @if ($yt && preg_match('/v=([\w-]+)/', $yt, $m))
                                            <iframe class="w-full max-w-md aspect-video rounded-md border"
                                                src="https://www.youtube.com/embed/{{ $m[1] }}"
                                                allowfullscreen></iframe>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <x-button type="button" icon="o-x-mark" class="btn-ghost text-error" title="Hapus"
                                wire:click="removeTopicResource({{ $ti }}, {{ $ri }})" />
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($topic['quiz']['enabled'] ?? false)
                <div class="mt-2 space-y-3 border-t pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold">Quiz Questions</span>
                        <x-button type="button" size="xs" icon="o-plus"
                            wire:click="addTopicQuizQuestion({{ $ti }})" outline class="border-gray-400">Add
                            Question</x-button>
                    </div>
                    @foreach ($topic['quiz']['questions'] ?? [] as $qi => $qq)
                        <div class="border rounded-lg p-3 space-y-2 bg-base-100">
                            <div class="flex items-center gap-2">
                                <x-select :options="[
                                    ['value' => 'multiple', 'label' => 'Multiple'],
                                    ['value' => 'essay', 'label' => 'Essay'],
                                ]" option-value="value" option-label="label" class="w-40"
                                    wire:model="topics.{{ $ti }}.quiz.questions.{{ $qi }}.type"
                                    wire:click="$refresh" />
                                <x-input class="flex-1" placeholder="Tulis pertanyaan"
                                    wire:model.defer="topics.{{ $ti }}.quiz.questions.{{ $qi }}.question" />
                                <x-button type="button" icon="o-trash" class="btn-ghost text-error" title="Hapus Soal"
                                    wire:click="removeTopicQuizQuestion({{ $ti }}, {{ $qi }})" />
                            </div>
                            @if (($qq['type'] ?? '') === 'multiple')
                                <div class="space-y-2">
                                    @foreach ($qq['options'] ?? [] as $oi => $opt)
                                        <div class="flex items-center gap-2">
                                            <x-input class="flex-1" placeholder="Option"
                                                wire:model.defer="topics.{{ $ti }}.quiz.questions.{{ $qi }}.options.{{ $oi }}" />
                                            <x-button type="button" icon="o-x-mark" class="btn-ghost text-error"
                                                wire:click="removeTopicQuizOption({{ $ti }}, {{ $qi }}, {{ $oi }})" />
                                        </div>
                                    @endforeach
                                    <x-button type="button" size="xs" icon="o-plus" outline
                                        wire:click="addTopicQuizOption({{ $ti }}, {{ $qi }})"
                                        class="border-gray-400">Add Option</x-button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="text-xs text-gray-400">There is no content.</div>
    @endforelse

    <div class="flex items-center justify-between pt-4">
        <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goBack">
            <x-icon name="o-arrow-left" class="size-4" />
            <span>Back</span>
        </x-ui.button>
        <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNext">
            <span>Next</span>
            <x-icon name="o-arrow-right" class="size-4" />
        </x-ui.button>
    </div>
</div>
