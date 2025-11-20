<div>
    <x-modal wire:model="show" title="Certification Action" subtitle="What would you like to do with this certification?" box-class="max-w-md" separator>
        <div class="space-y-4 mt-2">
            <div class="p-3 rounded border bg-gray-50">
                <p class="text-xs uppercase tracking-wide text-gray-500">Title</p>
                <p class="font-semibold text-gray-800 mt-1 truncate">{{ $title }}</p>
            </div>
            <div class="flex flex-col gap-3">
                <x-button wire:click="viewDetail" class="w-full bg-white hover:bg-gray-100 border text-gray-800" spinner="viewDetail">
                    View Detail
                </x-button>
                @role('admin')
                    <x-button wire:click="editCertification" class="w-full bg-primary text-white hover:opacity-90" spinner="editCertification">
                        Edit Certification
                    </x-button>
                @endrole
            </div>
        </div>
        <div class="mt-6 text-center">
            <button type="button" wire:click="close" class="text-sm text-gray-600 hover:text-gray-800 focus:outline-none">Cancel</button>
        </div>
    </x-modal>
</div>
