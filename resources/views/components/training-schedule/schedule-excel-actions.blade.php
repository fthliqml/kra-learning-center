<div class="flex items-center justify-center gap-2">
    <x-dropdown no-x-anchor right>
        <x-slot:trigger>
            <x-button class="btn-success h-10" wire:target="file" wire:loading.attr="disabled">
                <span class="flex items-center gap-2" wire:loading.remove wire:target="file">
                    <x-icon name="o-clipboard-document-list" class="size-4" />
                    Excel
                </span>
                <span class="flex items-center gap-2" wire:loading wire:target="file">
                    <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                </span>
            </x-button>
        </x-slot:trigger>

        <x-menu-item title="Export" icon="o-arrow-down-on-square" wire:click.stop="export" spinner="export" />

        <x-menu-item title="Import" icon="o-arrow-up-on-square" x-on:click="$dispatch('open-training-import')" />

        <x-menu-item title="Download Template" icon="o-document-arrow-down" wire:click.stop="downloadTemplate"
            spinner="downloadTemplate" />
    </x-dropdown>
</div>
