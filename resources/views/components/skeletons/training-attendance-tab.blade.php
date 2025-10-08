<div class="space-y-4 animate-pulse" aria-busy="true">
    <div class="flex justify-between items-start sm:items-center gap-4">
        <div class="h-6 bg-gray-200 rounded w-48"></div>
        <div class="h-8 bg-gray-200 rounded w-32"></div>
    </div>
    <div class="rounded-lg border border-gray-200 shadow">
        <div class="p-2 overflow-x-hidden">
            <div class="max-h-[400px] overflow-y-auto">
                <table class="w-full text-sm border-collapse table-fixed">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr>
                            <th class="py-3 px-2 text-center w-12">
                                <div class="h-3 w-6 mx-auto bg-gray-200 rounded"></div>
                            </th>
                            <th class="py-3 px-2 text-center w-28">
                                <div class="h-3 w-10 mx-auto bg-gray-200 rounded"></div>
                            </th>
                            <th class="py-3 px-3 text-left min-w-[180px]">
                                <div class="h-3 w-20 bg-gray-200 rounded"></div>
                            </th>
                            <th class="py-3 px-2 text-center w-40">
                                <div class="h-3 w-16 mx-auto bg-gray-200 rounded"></div>
                            </th>
                            <th class="py-3 px-2 text-center w-44">
                                <div class="h-3 w-14 mx-auto bg-gray-200 rounded"></div>
                            </th>
                            <th class="py-3 px-2 text-center min-w-[240px]">
                                <div class="h-3 w-16 mx-auto bg-gray-200 rounded"></div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (range(1, 5) as $r)
                            <tr class="even:bg-gray-50">
                                <td class="py-2.5 px-2 text-center">
                                    <div class="h-4 w-5 bg-gray-200 rounded mx-auto"></div>
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    <div class="h-4 w-14 bg-gray-200 rounded mx-auto"></div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="h-4 w-40 bg-gray-200 rounded"></div>
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    <div class="h-4 w-24 bg-gray-200 rounded mx-auto"></div>
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    <div class="h-5 w-32 bg-gray-200 rounded mx-auto"></div>
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    <div class="h-4 w-52 bg-gray-200 rounded mx-auto"></div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-4 py-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-[11px]">
            <div class="h-4 w-40 bg-gray-200 rounded"></div>
            <div class="flex gap-4">
                <div class="h-4 w-20 bg-gray-200 rounded"></div>
                <div class="h-4 w-20 bg-gray-200 rounded"></div>
                <div class="h-4 w-20 bg-gray-200 rounded"></div>
            </div>
        </div>
    </div>
</div>
