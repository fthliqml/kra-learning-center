<div wire:loading class="w-full p-4 overflow-x-auto">
    <table class="w-full table-auto border-collapse">
        <thead>
            <tr>
                @foreach (['No', 'Title', 'Group Comp', 'Status', 'Action'] as $col)
                    <th class="py-3 px-2 text-sm font-semibold text-gray-400">
                        <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (range(1, 10) as $i)
                <tr class="border-b border-gray-200">
                    @foreach (range(1, 5) as $j)
                        <td class="py-4 px-2"> {{-- py-4 untuk row lebih tinggi --}}
                            <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
