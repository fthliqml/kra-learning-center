@props([
    'placeholder' => 'Search',
    'class' => '',
])

<div class="relative h-10 w-full {{ $class }}">
    {{-- Icon --}}
    <x-icon name="o-magnifying-glass"
        class="absolute left-3 top-5 -translate-y-[45%] text-gray-500 w-4 h-4 pointer-events-none" />

    {{-- Input --}}
    <input type="text" placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'pl-10 shadow-[inset_0_4px_6px_rgba(0,0,0,0.15)] bg-[#E6F4FF] h-10
                            file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground
                            dark:bg-input/30 border-input flex w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base
                            transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm
                            file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm
                            focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[1px]
                            aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
        ]) }} />
</div>
