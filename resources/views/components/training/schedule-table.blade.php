    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$modules" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>
            {{-- Custom cell untuk kolom Action --}}
            @scope('cell_action', $module)
                <div class="flex gap-2 justify-center">

                    <!-- Edit -->
                    <x-button icon="o-pencil-square" class="btn-circle btn-ghost p-2 bg-tetriary" spinner
                        wire:click="openEditModal({{ $module->id }})" />

                    <!-- Delete -->
                    <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85" spinner
                        wire:click="$dispatch('confirm', {
                            title: 'Yakin mau hapus?',
                            text: 'Data yang sudah dihapus tidak bisa dikembalikan!',
                            action: 'deleteModule',
                            id: {{ $module->id }}
                        })" />
                </div>
            @endscope

        </x-table>
    </div>
