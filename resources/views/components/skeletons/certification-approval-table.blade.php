<div wire:loading class="w-full p-4 overflow-x-auto">
    <table class="w-full table-auto border-collapse">
        <thead>
            <tr>
                <th class="py-3 px-2 text-sm font-semibold text-gray-400 !text-center w-12 !md:w-[8%]">
                    <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                </th>
                <th class="py-3 px-2 text-sm font-semibold text-gray-400 !md:w-[50%]">
                    <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                </th>
                <th class="py-3 px-2 text-sm font-semibold text-gray-400 !text-center !md:w-[12%]">
                    <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                </th>
                <th class="py-3 px-2 text-sm font-semibold text-gray-400 !text-center !md:w-[14%]">
                    <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                </th>
                <th class="py-3 px-2 text-sm font-semibold text-gray-400 !text-center w-[60px]">
                    <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach (range(1, 10) as $i)
                <tr class="border-b border-gray-200">
                    <td class="py-4 px-2 !text-center w-12 !md:w-[8%]">
                        <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                    </td>
                    <td class="py-4 px-2 !md:w-[50%]">
                        <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                    </td>
                    <td class="py-4 px-2 !text-center !md:w-[12%]">
                        <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                    </td>
                    <td class="py-4 px-2 !text-center !md:w-[14%]">
                        <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                    </td>
                    <td class="py-4 px-2 !text-center w-[100px]">
                        <div class="h-6 bg-gray-300 rounded animate-pulse w-full"></div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
