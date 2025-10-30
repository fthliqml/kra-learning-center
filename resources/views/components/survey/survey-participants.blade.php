@php
    // Dummy data peserta
    $rows = [
        [
            'no' => 1,
            'nrp' => '210001',
            'name' => 'Budi Santoso',
            'section' => 'Production',
            'status' => 'filled',
        ],
        [
            'no' => 2,
            'nrp' => '210002',
            'name' => 'Siti Aminah',
            'section' => 'HR',
            'status' => 'not filled',
        ],
        [
            'no' => 3,
            'nrp' => '210003',
            'name' => 'Andi Wijaya',
            'section' => 'Maintenance',
            'status' => 'filled',
        ],
        [
            'no' => 4,
            'nrp' => '210004',
            'name' => 'Rina Dewi',
            'section' => 'Logistics',
            'status' => 'not filled',
        ],
    ];
    $headers = [
        ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
        ['key' => 'nrp', 'label' => 'NRP'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'section', 'label' => 'Section'],
        ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
    ];
@endphp

<x-table :headers="$headers" :rows="$rows" class="w-full">
    @scope('cell_status', $row)
        <span class="badge {{ $row['status'] === 'filled' ? 'badge-success' : 'badge-ghost' }}">
            {{ $row['status'] === 'filled' ? 'Filled' : 'Not Filled' }}
        </span>
    @endscope
</x-table>
