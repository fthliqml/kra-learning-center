<div class="{{ $errors->any() ? 'space-y-1' : 'space-y-4' }}">
    <x-input label="Course Title" placeholder="Enter course title" class="focus-within:border-0"
        wire:model.live.debounce.400ms="course.title" />

    <x-textarea label="About Course" placeholder="Describe this course" class="focus-within:border-0"
        wire:model.live.debounce.500ms="course.about" />

    <x-select label="Group Competency" :options="$groupOptions" option-value="value" option-label="label"
        placeholder="Select group" wire:change="$refresh" class="focus-within:border-0"
        wire:model="course.group_comp" />

    <div>
        <label class="block mb-2 text-sm font-medium">Choose Thumbnail</label>
        <div class="flex flex-col items-stretch w-full">
            <input id="thumbnail-input" type="file" wire:model="thumbnail" accept="image/*" class="sr-only" />
            <label for="thumbnail-input" class="cursor-pointer group block w-full">
                @php($finalThumb = $course['thumbnail_url'] ?? '')
                @if ($thumbnail)
                    <img src="{{ $thumbnail->temporaryUrl() }}"
                        class="w-3xl h-60 object-contain object-center rounded-lg shadow-md group-hover:ring-2 group-hover:ring-primary/60 transition border-1 border-gray-300 block mx-auto"
                        alt="Thumbnail preview (temporary)" />
                @elseif($finalThumb)
                    <img src="{{ $finalThumb }}"
                        class="w-3xl h-60 object-contain object-center rounded-lg shadow-md group-hover:ring-2 group-hover:ring-primary/60 transition border-1 border-gray-300 block mx-auto"
                        alt="Thumbnail" />
                @else
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

    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <x-ui.save-draft-status action="saveDraft" :dirty="$isDirty" :ever="$hasEverSaved" :persisted="$persisted" />
        <div class="flex gap-2">
            <x-ui.button type="button" variant="primary" class="gap-2" onclick="history.back()">
                <x-icon name="o-arrow-left" class="size-4" />
                <span>Back</span>
            </x-ui.button>
            <x-ui.button type="button" variant="primary" class="gap-2" wire:click="saveAndNext">
                <span>Next</span>
                <x-icon name="o-arrow-right" class="size-4" />
            </x-ui.button>
        </div>
    </div>
</div>
