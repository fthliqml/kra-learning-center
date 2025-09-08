@props(['class' => ''])

<thead data-slot="table-header" {{ $attributes->merge(['class' => "[&_tr]:border-b $class"]) }}>
    {{ $slot }}
</thead>
