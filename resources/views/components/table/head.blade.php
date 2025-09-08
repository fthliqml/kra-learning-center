@props(['class' => ''])

<th data-slot="table-head"
    {{ $attributes->merge([
        'class' => "text-foreground h-10 px-2 text-left align-middle font-medium whitespace-nowrap $class",
    ]) }}>
    {{ $slot }}
</th>
