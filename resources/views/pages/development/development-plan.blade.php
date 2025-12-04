<div>
    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-5 mb-5 lg:mb-9 items-center justify-between">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start whitespace-nowrap">
            Development Plan
        </h1>

        <div class="flex items-center justify-center gap-2">
            <!-- Add Button -->
            <x-ui.button variant="primary" wire:click="openAddModal" wire:target="openAddModal" class="h-10"
                wire:loading.attr="readonly">
                <span wire:loading.remove wire:target="openAddModal" class="flex items-center gap-2">
                    <x-icon name="o-plus" class="size-4" />
                    Add
                </span>
                <span wire:loading wire:target="openAddModal">
                    <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                </span>
            </x-ui.button>
        </div>
    </div>

    {{-- Main Content - Personal Information --}}
    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Photo Card --}}
        <div class="w-full lg:w-2/5">
            <div class="rounded-xl border border-gray-200 bg-white p-6 h-full flex items-center justify-center">
                <div class="w-full max-w-[280px] aspect-[3/4] rounded-lg bg-gray-100 overflow-hidden">
                    @if ($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
                            class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-primary/10">
                            <span class="text-primary text-6xl font-bold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Information Card --}}
        <div class="w-full lg:w-3/5">
            <div class="rounded-xl border border-gray-200 bg-white p-6 h-full">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Personal Information</h2>

                <div class="space-y-5">
                    {{-- Name --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Name</p>
                        <p class="text-base font-medium text-gray-800">{{ $user->name }}</p>
                    </div>

                    {{-- NRP --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">NRP</p>
                        <p class="text-base font-medium text-gray-800">{{ $user->NRP }}</p>
                    </div>

                    {{-- Division --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Division</p>
                        <p class="text-base font-medium text-gray-800">{{ $user->division ?? '-' }}</p>
                    </div>

                    {{-- Department --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Department</p>
                        <p class="text-base font-medium text-gray-800">{{ $user->department ?? '-' }}</p>
                    </div>

                    {{-- Section --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Section</p>
                        <p class="text-base font-medium text-gray-800">{{ $user->section ?? '-' }}</p>
                    </div>

                    {{-- Position --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Position</p>
                        <p class="text-base font-medium text-gray-800">{{ $user->position ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Modal --}}
    <x-modal wire:model="addModal" title="Add Development Plan" separator box-class="max-w-3xl h-fit">
        <x-form wire:submit="save" no-separator>
            {{-- Tabs --}}
            <div class="border-b border-gray-200">
                <nav class="flex gap-6 -mb-px">
                    <button type="button" wire:click="setActiveTab('training')"
                        class="py-2 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'training' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Training
                    </button>
                    <button type="button" wire:click="setActiveTab('self-learning')"
                        class="py-2 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'self-learning' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Self Learning
                    </button>
                    <button type="button" wire:click="setActiveTab('mentoring')"
                        class="py-2 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'mentoring' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Mentoring
                    </button>
                    <button type="button" wire:click="setActiveTab('project')"
                        class="py-2 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'project' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Project Assignment
                    </button>
                </nav>
            </div>

            {{-- Training Tab --}}
            @if ($activeTab === 'training')
                <div class="space-y-4">
                    @foreach ($trainingPlans as $index => $plan)
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-4">
                                <x-choices label="Group Competency"
                                    wire:model.live="trainingPlans.{{ $index }}.group" :options="$typeOptions"
                                    option-value="value" option-label="label" placeholder="Select group"
                                    class="focus-within:border-0" single />
                            </div>
                            <div class="col-span-8">
                                <x-choices label="Competency"
                                    wire:model="trainingPlans.{{ $index }}.competency_id" :options="$this->getCompetenciesByType($trainingPlans[$index]['group'])"
                                    option-value="value" option-label="label" placeholder="Select competency"
                                    class="focus-within:border-0" single searchable />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Self Learning Tab --}}
            @if ($activeTab === 'self-learning')
                <div class="space-y-4">
                    <x-input label="Title" wire:model="selfLearning.title" placeholder="Enter title"
                        class="focus-within:border-0" />

                    <x-textarea label="Objective" wire:model="selfLearning.objective" placeholder="Enter objective"
                        class="focus-within:border-0" rows="3" />

                    <x-choices label="Mentor/Superior" wire:model="selfLearning.mentor_id" :options="$mentors"
                        option-value="value" option-label="label" placeholder="Select mentor"
                        class="focus-within:border-0" single searchable />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Date In" wire:model="selfLearning.start_date" type="date"
                            class="focus-within:border-0" />
                        <x-input label="Date Out" wire:model="selfLearning.end_date" type="date"
                            class="focus-within:border-0" />
                    </div>
                </div>
            @endif

            {{-- Mentoring Tab --}}
            @if ($activeTab === 'mentoring')
                <div class="space-y-4">
                    <x-choices label="Mentor/Superior" wire:model="mentoring.mentor_id" :options="$mentors"
                        option-value="value" option-label="label" placeholder="Select mentor"
                        class="focus-within:border-0" single searchable />

                    <x-textarea label="Objective" wire:model="mentoring.objective" placeholder="Enter objective"
                        class="focus-within:border-0" rows="3" />

                    <x-choices label="Method" wire:model="mentoring.method" :options="$methodOptions" option-value="value"
                        option-label="label" placeholder="Select method" class="focus-within:border-0" single />

                    <x-input label="Frequency" wire:model="mentoring.frequency" type="number"
                        placeholder="Enter frequency" class="focus-within:border-0" />

                    <x-input label="Duration" wire:model="mentoring.duration" type="number"
                        placeholder="Enter duration" class="focus-within:border-0" />
                </div>
            @endif

            {{-- Project Assignment Tab --}}
            @if ($activeTab === 'project')
                <div class="space-y-4">
                    <x-input label="Project Name" wire:model="project.name" placeholder="Enter project name"
                        class="focus-within:border-0" />

                    <x-textarea label="Objective" wire:model="project.objective" placeholder="Enter objective"
                        class="focus-within:border-0" rows="3" />

                    <x-choices label="Mentor/Superior" wire:model="project.mentor_id" :options="$mentors"
                        option-value="value" option-label="label" placeholder="Select mentor"
                        class="focus-within:border-0" single searchable />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Date In" wire:model="project.start_date" type="date"
                            class="focus-within:border-0" />
                        <x-input label="Date Out" wire:model="project.end_date" type="date"
                            class="focus-within:border-0" />
                    </div>
                </div>
            @endif

            <x-slot:actions class="mt-5">
                <x-ui.button type="button" wire:click="saveDraft" class="btn-outline" spinner="saveDraft">
                    Save Draft
                </x-ui.button>
                <x-ui.button @click="$wire.closeAddModal()" type="button">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" class="btn-primary !text-white" spinner="save">
                    Save
                </x-ui.button>
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
