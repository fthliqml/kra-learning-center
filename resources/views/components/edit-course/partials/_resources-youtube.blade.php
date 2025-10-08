@php($ytExists = collect($section['resources'])->contains(fn($r) => ($r['type'] ?? '') === 'youtube'))
@if ($ytExists)
    <div class="space-y-2">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">YouTube Videos</p>
        @foreach ($section['resources'] as $ri => $res)
            @if (($res['type'] ?? '') === 'youtube')
                @php($resKey = 't' . $ti . '-s' . $si . '-r' . $ri)
                <div
                    class="flex items-start gap-3 rounded-md px-3 py-2 shadow-sm ring-1 transition bg-base-100/70 hover:ring-base-300 {{ in_array($resKey, $errorResourceKeys ?? []) ? 'ring-error/60 border border-error/60 bg-error/5' : 'ring-base-300/40 border border-base-300/40' }}">
                    <div class="flex-1 space-y-1">
                        <x-input placeholder="https://www.youtube.com/watch?v=..."
                            wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.resources.{{ $ri }}.url"
                            wire:change="$refresh" />
                        @php($yt = $res['url'] ?? '')
                        @if ($yt && preg_match('/v=([\w-]+)/', $yt, $m))
                            <iframe class="w-full max-w-md aspect-video rounded-md ring-1 ring-base-300/50"
                                src="https://www.youtube.com/embed/{{ $m[1] }}" allowfullscreen></iframe>
                        @endif
                    </div>
                    <x-button type="button" icon="o-x-mark" class="btn-ghost text-error" title="Delete"
                        wire:click="removeSectionResource({{ $ti }}, {{ $si }}, {{ $ri }})" />
                </div>
            @endif
        @endforeach
    </div>
@endif
