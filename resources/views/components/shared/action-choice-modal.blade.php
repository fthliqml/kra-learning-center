<div>
    <x-modal wire:model="show" :title="$title" box-class="max-w-md">
        <div class="space-y-4">
            <p class="text-sm text-gray-600">{{ $message }}</p>
            <div class="flex flex-col space-y-2">
                @foreach ($actions as $action)
                    <x-button :label="$action['label']" :class="'btn-' . ($action['variant'] ?? 'outline')" wire:click="choose('{{ $action['event'] }}')" />
                @endforeach
            </div>
        </div>
        <x-slot:actions>
            <x-button label="Cancel" class="btn-ghost" wire:click="close" />
        </x-slot:actions>
    </x-modal>
</div>
