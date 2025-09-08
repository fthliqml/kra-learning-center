@props(['type' => 'body', 'class' => ''])

<tr data-slot="table-row"
    {{ $attributes->merge([
        'class' =>
            'hover:bg-muted/50 data-[state=selected]:bg-muted border-b transition-colors ' .
            ($type === 'head' ? 'odd:bg-white' : 'odd:bg-[#EDEDED] odd:hover:bg-[#EDEDED] even:bg-white') .
            " $class",
    ]) }}>
    {{ $slot }}
</tr>
