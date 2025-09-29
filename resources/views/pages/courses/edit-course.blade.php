<div class="space-y-6">
    <x-tabs wire:model="activeTab" class="bg-base-100 p-3 rounded-box shadow-sm">
        {{-- TAB: Course Info --}}
        <x-tab name="course-info" label="Course Info" icon="m-document-text">
            <div class="space-y-4">
                <x-input label="Course Title" placeholder="Enter course title" class="focus-within:border-0"
                    wire:model.defer="course.title" />

                <x-textarea label="About Course" placeholder="Describe this course" class="focus-within:border-0"
                    wire:model.defer="course.about" />

                <x-select label="Group Competency" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="Select group" class="focus-within:border-0" wire:model="course.group_comp" />

                <div>
                    <label class="block mb-2 text-sm font-medium">Choose Thumbnail</label>

                    <div class="flex flex-col items-stretch w-full">
                        <!-- Hidden input: clicking the preview/placeholder will trigger this -->
                        <input id="thumbnail-input" type="file" wire:model="thumbnail" accept="image/*"
                            class="sr-only" />

                        <!-- Clickable area (both preview and placeholder) -->
                        <label for="thumbnail-input" class="cursor-pointer group block w-full">
                            @if ($thumbnail)
                                <!-- Preview image -->
                                <img src="{{ $thumbnail->temporaryUrl() }}"
                                    class="w-3xl h-60 object-cover object-center rounded-lg shadow-md group-hover:ring-2 group-hover:ring-primary/60 transition border-1 border-gray-300 block mx-auto"
                                    alt="Thumbnail preview" />
                            @else
                                <!-- Upload icon placeholder -->
                                <div
                                    class="w-full h-60 flex items-center justify-center border-2 border-dashed rounded-lg text-gray-400 group-hover:border-primary/60 group-hover:text-primary/70 transition-colors mx-auto">
                                    <x-icon name="o-arrow-up-tray" class="w-12 h-12" />
                                </div>
                            @endif
                        </label>

                        @error('thumbnail')
                            <div class="text-error text-xs mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <x-ui.button type="button" variant="primary" class="gap-2" onclick="history.back()">
                        <x-icon name="o-arrow-left" class="size-4" />
                        <span>Back</span>
                    </x-ui.button>
                    <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNextTab('pretest')">
                        <span>Next</span>
                        <x-icon name="o-arrow-right" class="size-4" />
                    </x-ui.button>
                </div>
            </div>
        </x-tab>

        {{-- TAB: Pretest --}}
        <x-tab name="pretest" label="Pretest" icon="m-clipboard-document-list">
            <div class="space-y-4">
                @foreach ($questions as $qi => $q)
                    <div class="border rounded-xl p-3 bg-base-100">
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ $loop->iteration }}</span>
                            <x-select :options="[
                                ['value' => 'multiple', 'label' => 'Multiple Choice'],
                                ['value' => 'essay', 'label' => 'Essay'],
                            ]" option-value="value" option-label="label" class="w-52"
                                wire:model="questions.{{ $qi }}.type" wire:click="$refresh" />

                            <div class="flex-1 relative">
                                <x-input class="w-full pr-10 focus-within:border-0" placeholder="Write the question"
                                    wire:model.defer="questions.{{ $qi }}.question" />
                                <button type="button" title="Remove question"
                                    class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                                    wire:click="removeQuestionRow({{ $qi }})">
                                    <x-icon name="o-trash" class="size-4" />
                                </button>
                            </div>
                        </div>

                        @if (($q['type'] ?? '') === 'multiple')
                            <div class="space-y-2">
                                @foreach ($q['options'] ?? [] as $oi => $opt)
                                    <div class="flex items-center gap-2">
                                        <x-input class="flex-1 pr-10 focus-within:border-0" placeholder="Option text"
                                            wire:model.defer="questions.{{ $qi }}.options.{{ $oi }}" />
                                        <x-button icon="o-trash" class="btn-ghost text-red-500" title="Remove option"
                                            wire:click="removeOptionRow({{ $qi }}, {{ $oi }})" />
                                    </div>
                                @endforeach
                                <x-button type="button" size="sm" class="border-gray-400" outline icon="o-plus"
                                    wire:click="addOptionRow({{ $qi }})">Add Option</x-button>
                            </div>
                        @endif
                    </div>
                @endforeach

                <x-button type="button" variant="primary" outline icon="o-plus" wire:click="addQuestionRow"
                    class="border-gray-400">Add
                    Question</x-button>

                <div class="flex items-center justify-between pt-2">
                    <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNextTab('course-info')">
                        <x-icon name="o-arrow-left" class="size-4" />
                        <span>Back</span>
                    </x-ui.button>
                    <x-ui.button type="button" variant="primary" class="gap-2"
                        wire:click="goNextTab('learning-module')">
                        <span>Next</span>
                        <x-icon name="o-arrow-right" class="size-4" />
                    </x-ui.button>
                </div>
            </div>
        </x-tab>

        {{-- TAB: Learning Module --}}
        <x-tab name="learning-module" label="Learning Module" icon="m-academic-cap">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Section</h3>
                    <x-button type="button" size="sm" icon="o-plus" wire:click="addTopic"
                        class="border-gray-400" outline>Add
                        Section</x-button>
                </div>

                @forelse($topics as $ti => $topic)
                    <div class="border rounded-xl p-4 bg-base-100 space-y-4">
                        <div class="flex items-start gap-3">
                            <span
                                class="inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-full bg-secondary/10 text-secondary text-xs font-semibold">{{ $loop->iteration }}</span>
                            <div class="flex-1 space-y-2">
                                <x-input placeholder="Judul Topik (misal: Safety Induction)"
                                    wire:model.defer="topics.{{ $ti }}.title" />
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
                            <x-button type="button" icon="o-trash" class="btn-ghost text-red-500"
                                title="Hapus Topik" wire:click="removeTopic({{ $ti }})" />
                        </div>

                        {{-- Resources --}}
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
                                                        <p class="text-xs text-success">File siap diupload:
                                                            {{ $res['file']->getClientOriginalName() }}</p>
                                                    @endif
                                                </div>
                                            @elseif(($res['type'] ?? '') === 'youtube')
                                                <div class="space-y-1">
                                                    <label class="text-xs font-medium">YouTube URL</label>
                                                    <x-input placeholder="https://www.youtube.com/watch?v=..."
                                                        wire:model.defer="topics.{{ $ti }}.resources.{{ $ri }}.url" />
                                                    @php($yt = $res['url'] ?? '')
                                                    @if ($yt && preg_match('/v=([\w-]+)/', $yt, $m))
                                                        <iframe class="w-full max-w-md aspect-video rounded-md border"
                                                            src="https://www.youtube.com/embed/{{ $m[1] }}"
                                                            allowfullscreen></iframe>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <x-button type="button" icon="o-x-mark" class="btn-ghost text-error"
                                            title="Hapus"
                                            wire:click="removeTopicResource({{ $ti }}, {{ $ri }})" />
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Quiz Section --}}
                        @if ($topic['quiz']['enabled'] ?? false)
                            <div class="mt-2 space-y-3 border-t pt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold">Quiz Questions</span>
                                    <x-button type="button" size="xs" icon="o-plus"
                                        wire:click="addTopicQuizQuestion({{ $ti }})" outline
                                        class="border-gray-400">Add
                                        Question</x-button>
                                </div>
                                @foreach ($topic['quiz']['questions'] ?? [] as $qi => $qq)
                                    <div class="border rounded-lg p-3 space-y-2 bg-base-100">
                                        <div class="flex items-center gap-2">
                                            <x-select :options="[
                                                ['value' => 'multiple', 'label' => 'Multiple'],
                                                ['value' => 'essay', 'label' => 'Essay'],
                                            ]" option-value="value" option-label="label"
                                                class="w-40"
                                                wire:model="topics.{{ $ti }}.quiz.questions.{{ $qi }}.type"
                                                wire:click="$refresh" />
                                            <x-input class="flex-1" placeholder="Tulis pertanyaan"
                                                wire:model.defer="topics.{{ $ti }}.quiz.questions.{{ $qi }}.question" />
                                            <x-button type="button" icon="o-trash" class="btn-ghost text-error"
                                                title="Hapus Soal"
                                                wire:click="removeTopicQuizQuestion({{ $ti }}, {{ $qi }})" />
                                        </div>
                                        @if (($qq['type'] ?? '') === 'multiple')
                                            <div class="space-y-2">
                                                @foreach ($qq['options'] ?? [] as $oi => $opt)
                                                    <div class="flex items-center gap-2">
                                                        <x-input class="flex-1" placeholder="Option"
                                                            wire:model.defer="topics.{{ $ti }}.quiz.questions.{{ $qi }}.options.{{ $oi }}" />
                                                        <x-button type="button" icon="o-x-mark"
                                                            class="btn-ghost text-error"
                                                            wire:click="removeTopicQuizOption({{ $ti }}, {{ $qi }}, {{ $oi }})" />
                                                    </div>
                                                @endforeach
                                                <x-button type="button" size="xs" icon="o-plus" outline
                                                    wire:click="addTopicQuizOption({{ $ti }}, {{ $qi }})"
                                                    class="border-gray-400">Add
                                                    Option</x-button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-xs text-gray-400">Belum ada topik.</div>
                @endforelse
            </div>
            <div class="flex items-center justify-between pt-4">
                <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNextTab('pretest')">
                    <x-icon name="o-arrow-left" class="size-4" />
                    <span>Back</span>
                </x-ui.button>
                <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNextTab('post-test')">
                    <span>Next</span>
                    <x-icon name="o-arrow-right" class="size-4" />
                </x-ui.button>
            </div>
        </x-tab>

        {{-- TAB: Post Test --}}
        <x-tab name="post-test" label="Post Test" icon="m-check-badge">
            <div class="space-y-4">
                @foreach ($postQuestions as $qi => $q)
                    <div class="border rounded-xl p-3 bg-base-100">
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ $loop->iteration }}</span>
                            <x-select :options="[
                                ['value' => 'multiple', 'label' => 'Multiple Choice'],
                                ['value' => 'essay', 'label' => 'Essay'],
                            ]" option-value="value" option-label="label" class="w-52"
                                wire:model="postQuestions.{{ $qi }}.type" wire:click="$refresh" />

                            <div class="flex-1 relative">
                                <x-input class="w-full pr-10 focus-within:border-0" placeholder="Write the question"
                                    wire:model.defer="postQuestions.{{ $qi }}.question" />
                                <button type="button" title="Remove question"
                                    class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                                    wire:click="removePostQuestionRow({{ $qi }})">
                                    <x-icon name="o-trash" class="size-4" />
                                </button>
                            </div>
                        </div>

                        @if (($q['type'] ?? '') === 'multiple')
                            <div class="space-y-2">
                                @foreach ($q['options'] ?? [] as $oi => $opt)
                                    <div class="flex items-center gap-2">
                                        <x-input class="flex-1 pr-10 focus-within:border-0" placeholder="Option text"
                                            wire:model.defer="postQuestions.{{ $qi }}.options.{{ $oi }}" />
                                        <x-button icon="o-trash" class="btn-ghost text-red-500" title="Remove option"
                                            wire:click="removePostOptionRow({{ $qi }}, {{ $oi }})" />
                                    </div>
                                @endforeach
                                <x-button type="button" size="sm" class="border-gray-400" outline
                                    icon="o-plus" wire:click="addPostOptionRow({{ $qi }})">Add
                                    Option</x-button>
                            </div>
                        @endif
                    </div>
                @endforeach

                <x-button type="button" variant="primary" outline icon="o-plus" wire:click="addPostQuestionRow"
                    class="border-gray-400">Add Question</x-button>

                <div class="flex items-center justify-between pt-2">
                    <x-ui.button type="button" variant="primary" class="gap-2"
                        wire:click="goNextTab('learning-module')">
                        <x-icon name="o-arrow-left" class="size-4" />
                        <span>Back</span>
                    </x-ui.button>
                    <x-ui.button type="button" variant="primary" class="gap-2">
                        <x-icon name="o-check" class="size-4" />
                        <span>Finish</span>
                    </x-ui.button>
                </div>
            </div>
        </x-tab>
    </x-tabs>
</div>
