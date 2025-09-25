<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">Courses Management</h1>
        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <!-- Controls intentionally minimal for now (filter/export/add later) -->
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$courses" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>

            {{-- Group Comp from related training --}}
            @scope('cell_group_comp', $course)
                {{ optional($course->training)->group_comp ?? '-' }}
            @endscope

            {{-- Status badge --}}
            @scope('cell_status', $course)
                @php
                    $status = $course->status ?? 'inactive';
                    $variant = $status === 'active' ? 'success' : 'neutral';
                @endphp
                <x-badge :value="str($status)->title()" :class="'badge-' . $variant" />
            @endscope

            {{-- Custom cell untuk kolom Action --}}
            @scope('cell_action', $course)
                <div class="flex gap-2 justify-center">
                    <!-- Delete -->
                    <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85" spinner
                        wire:click="$dispatch('confirm', {
                            title: 'Yakin mau hapus?',
                            text: 'Data yang sudah dihapus tidak bisa dikembalikan!',
                            action: 'deleteCourse',
                            id: {{ $course->id }}
                        })" />
                </div>
            @endscope
        </x-table>
    </div>
    {{-- No modal/edit for now --}}
</div>
