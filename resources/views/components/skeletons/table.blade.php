@props(['columns' => 5, 'rows' => 10, 'targets' => 'search'])

<div wire:loading wire:target="{{ $targets }}" class="w-full p-4 overflow-x-auto">
    <table class="w-full table-auto border-collapse">
        <thead>
            <tr>
                @foreach (range(1, $columns) as $col)
                    <th class="py-3 px-2 text-sm font-semibold text-gray-400">
                        <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (range(1, $rows) as $i)
                <tr class="border-b border-gray-200">
                    @foreach (range(1, $columns) as $j)
                        <td class="py-4 px-2">
                            <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
