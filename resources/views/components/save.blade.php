    <x-modal :show="false" max-width="3xl" x-data="{
        mode: 'create',
        formAction: '{{ route('training-module.store') }}',
        formData: {
            id: null,
            title: '',
            group_comp: '',
            objective: '',
            training_content: '',
            method: '',
            duration: '',
            frequency: ''
        }
    }"
        x-on:open-create-modal.window="
        mode = 'create';
        formAction = '{{ route('training-module.store') }}';
        formData = {id:null, title:'', group_comp:'', objective:'', training_content:'', method:'', duration:'', frequency:''};
        show = true;
    "
        x-on:open-edit-modal.window="
        mode = 'edit';
        formAction = `/training/module/${$event.detail.id}`;
        formData = $event.detail;
        show = true;
    ">
        <form :action="formAction" method="POST">
            @csrf
            <template x-if="mode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-modal.header class="pb-5 border-b border-gray-500">
                <x-modal.title
                    x-text="mode === 'create' ? 'Add Training Module' : 'Edit Training Module'"></x-modal.title>
            </x-modal.header>

            <div class="space-y-6 p-6">
                {{-- Title --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                    <input type="text" name="title" x-model="formData.title"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none"
                        placeholder="Title of the training module...">
                </div>

                {{-- Group Competency --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Group Competency</label>
                    <div class="relative">
                        <select name="group_comp" x-model="formData.group_comp"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none appearance-none bg-white">
                            <option value="" disabled>Select Group Competency</option>
                            <option value="BMC">BMC</option>
                            <option value="BC">BC</option>
                            <option value="MMP">MMP</option>
                            <option value="LC">LC</option>
                            <option value="MDP">MDP</option>
                            <option value="TOC">TOC</option>
                        </select>

                    </div>
                </div>

                {{-- Objective --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Objective</label>
                    <textarea name="objective" x-model="formData.objective"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none !min-h-[100px]"
                        placeholder="Describe the training objectives..."></textarea>
                </div>

                {{-- Training Content --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Training Content</label>
                    <textarea name="training_content" x-model="formData.training_content"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none !min-h-[100px]"
                        placeholder="Outline the main topics..."></textarea>
                </div>

                {{-- Method --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Method</label>
                    <input type="text" name="method" x-model="formData.method"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none"
                        placeholder="Describe the development concept...">
                </div>

            </div>

            <x-modal.footer class="!justify-between">
                <x-ui.button class="!bg-gray-200 !text-black" @click="$dispatch('close-modal');"
                    type="button">Cancel</x-ui.button>
                <x-ui.button variant="primary" type="submit">
                    <span x-text="mode === 'create' ? 'Save' : 'Update'"></span>
                    <x-lucide-save class="size-[15px]" />
                </x-ui.button>
            </x-modal.footer>
        </form>
    </x-modal>
