@php($pdfExists = collect($section['resources'])->contains(fn($r) => ($r['type'] ?? '') === 'pdf'))
@if ($pdfExists)
    <div class="space-y-2">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">PDF Modules</p>
        @foreach ($section['resources'] as $ri => $res)
            @if (($res['type'] ?? '') === 'pdf')
                @php($resKey = 't' . $ti . '-s' . $si . '-r' . $ri)
                <div
                    class="flex items-start gap-3 rounded-md px-3 py-2 shadow-sm ring-1 transition bg-base-100/70 hover:ring-base-300 {{ in_array($resKey, $errorResourceKeys ?? []) ? 'ring-error/60 border border-error/60 bg-error/5' : 'ring-base-300/40 border border-base-300/40' }}">
                    <div class="flex-1 space-y-1">
                        <input type="file" accept="application/pdf"
                            wire:model="topics.{{ $ti }}.sections.{{ $si }}.resources.{{ $ri }}.file"
                            class="file-input file-input-bordered file-input-sm w-full max-w-md" />
                        @php($pdfUrl = $res['url'] ?? '')
                        @if ($pdfUrl)
                            <a href="{{ $pdfUrl }}" target="_blank"
                                class="mt-1 inline-flex items-center gap-1 text-[11px] text-primary hover:underline">
                                <x-icon name="o-arrow-top-right-on-square" class="size-3" />
                                <span>Open PDF</span>
                            </a>
                        @else
                            <p class="text-[11px] text-gray-400">No file uploaded yet.</p>
                        @endif
                    </div>
                    <x-button type="button" icon="o-x-mark" class="btn-ghost text-error" title="Delete"
                        wire:click="removeSectionResource({{ $ti }}, {{ $si }}, {{ $ri }})" />
                </div>
            @endif
        @endforeach
    </div>
@endif
