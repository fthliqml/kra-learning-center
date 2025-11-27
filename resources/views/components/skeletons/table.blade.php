@props([
    'columns' => 5,
    'rows' => 10,
    'targets' => 'search,filter',
])

<div wire:loading wire:target="{{ $targets }}" class="w-full p-4 overflow-x-auto">
    <table class="w-full table-auto border-collapse">
        <thead>
            <tr>
                @for ($i = 0; $i < $columns; $i++)
                    <th class="py-3 px-2 text-sm font-semibold text-gray-400">
                        <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                    </th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $rows; $i++)
                <tr class="border-b border-gray-200">
                    @for ($j = 0; $j < $columns; $j++)
                        <td class="py-4 px-2">
                            <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                        </td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>
