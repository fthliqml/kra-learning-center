@props(['targets' => 'selectedYear'])

<div wire:loading.flex wire:target="{{ $targets }}" class="flex-col gap-6">
    {{-- Stats Cards Skeleton --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-1">
        @foreach (range(1, 4) as $i)
            <div class="rounded-xl border border-gray-200 bg-white p-4 animate-pulse">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-gray-200 w-10 h-10"></div>
                    <div class="space-y-2">
                        <div class="h-6 w-8 bg-gray-300 rounded"></div>
                        <div class="h-3 w-20 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Main Content Skeleton --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Personal Info Card Skeleton --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 animate-pulse">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 rounded-full bg-gray-200"></div>
                    <div class="space-y-2">
                        <div class="h-5 w-32 bg-gray-300 rounded"></div>
                        <div class="h-4 w-24 bg-gray-200 rounded"></div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <div class="h-4 w-16 bg-gray-200 rounded"></div>
                        <div class="h-4 w-24 bg-gray-300 rounded"></div>
                    </div>
                    <div class="flex justify-between">
                        <div class="h-4 w-16 bg-gray-200 rounded"></div>
                        <div class="h-4 w-24 bg-gray-300 rounded"></div>
                    </div>
                </div>
            </div>

            {{-- Chart Card Skeleton --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 animate-pulse">
                <div class="h-5 w-40 bg-gray-300 rounded mb-4"></div>
                <div class="flex items-center justify-center h-[200px]">
                    <div class="w-40 h-40 rounded-full bg-gray-200"></div>
                </div>
                <div class="flex justify-center gap-4 mt-4">
                    @foreach (range(1, 4) as $i)
                        <div class="h-3 w-16 bg-gray-200 rounded"></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Training Plans Skeleton --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 animate-pulse">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-5 h-5 bg-gray-200 rounded"></div>
                    <div class="h-5 w-32 bg-gray-300 rounded"></div>
                </div>
                <div class="space-y-3">
                    @foreach (range(1, 2) as $i)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="space-y-2 flex-1">
                                <div class="h-4 w-48 bg-gray-300 rounded"></div>
                                <div class="h-3 w-24 bg-gray-200 rounded"></div>
                            </div>
                            <div class="h-6 w-16 bg-gray-200 rounded-full"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Self Learning Skeleton --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 animate-pulse">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-5 h-5 bg-gray-200 rounded"></div>
                    <div class="h-5 w-28 bg-gray-300 rounded"></div>
                </div>
                <div class="space-y-3">
                    @foreach (range(1, 2) as $i)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="space-y-2 flex-1">
                                <div class="h-4 w-40 bg-gray-300 rounded"></div>
                                <div class="h-3 w-56 bg-gray-200 rounded"></div>
                            </div>
                            <div class="h-6 w-16 bg-gray-200 rounded-full"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Mentoring & Projects Skeleton --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Mentoring --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 animate-pulse">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-5 h-5 bg-gray-200 rounded"></div>
                        <div class="h-5 w-24 bg-gray-300 rounded"></div>
                    </div>
                    <div class="space-y-3">
                        @foreach (range(1, 2) as $i)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="h-4 w-32 bg-gray-300 rounded"></div>
                                    <div class="h-5 w-14 bg-gray-200 rounded-full"></div>
                                </div>
                                <div class="h-3 w-20 bg-gray-200 rounded"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Projects --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 animate-pulse">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-5 h-5 bg-gray-200 rounded"></div>
                        <div class="h-5 w-20 bg-gray-300 rounded"></div>
                    </div>
                    <div class="space-y-3">
                        @foreach (range(1, 2) as $i)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="h-4 w-28 bg-gray-300 rounded"></div>
                                    <div class="h-5 w-14 bg-gray-200 rounded-full"></div>
                                </div>
                                <div class="h-3 w-20 bg-gray-200 rounded"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
