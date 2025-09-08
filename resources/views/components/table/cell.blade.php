@props(['class' => ''])

<td data-slot="table-cell" {{ $attributes->merge([
    'class' => "p-2 align-middle whitespace-nowrap $class",
]) }}>
    {{ $slot }}
</td>
