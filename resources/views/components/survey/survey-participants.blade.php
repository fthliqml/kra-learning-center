<x-table :headers="$headers" :rows="$rows" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3" with-pagination>
    @scope('cell_status', $row)
        <span class="badge {{ $row['status'] === 'filled' ? 'badge-success' : 'badge-ghost' }}">
            {{ $row['status'] === 'filled' ? 'Filled' : 'Not Filled' }}
        </span>
    @endscope
</x-table>
