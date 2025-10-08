<div class="space-y-3" aria-live="polite" aria-busy="true">
    <span class="sr-only">Loading trainings…</span>
    @for ($i = 0; $i < ($count ?? 5); $i++)
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-3 flex gap-4 items-start animate-pulse">
            <div class="flex flex-col items-center w-12 sm:w-14 gap-1">
                <div class="h-2 w-8 rounded bg-gray-200"></div>
                <div class="h-5 sm:h-7 w-6 sm:w-8 rounded bg-gray-300"></div>
                <div class="h-2 w-6 rounded bg-gray-200"></div>
            </div>
            <div class="flex-1 min-w-0 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-gray-300"></span>
                    <div class="h-4 w-32 sm:w-44 rounded bg-gray-200"></div>
                    <div class="h-4 w-10 rounded bg-gray-100 border border-gray-200"></div>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-gray-200"></span>
                    <div class="h-3 w-40 sm:w-64 rounded bg-gray-100"></div>
                </div>
                <div class="h-3 w-48 sm:w-56 rounded bg-gray-100"></div>
            </div>
            <div class="self-center text-gray-300">
                <div class="w-4 h-4 sm:w-5 sm:h-5 bg-gray-200 rounded"></div>
            </div>
        </div>
    @endfor
</div>
