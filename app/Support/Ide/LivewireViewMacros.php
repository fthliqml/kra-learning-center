<?php

namespace App\Support\Ide;

/**
 * IDE-only contract for Livewire's View macros.
 *
 * Livewire adds `layout()` and `layoutData()` to the View instance at runtime.
 * Static analyzers don't see macros, so we use this interface for doc-casting.
 */
interface LivewireViewMacros
{
    /**
     * @param  string  $layout
     * @param  array<string, mixed>  $data
     */
    public function layout(string $layout, array $data = []);

    /**
     * @param  array<string, mixed>  $data
     */
    public function layoutData(array $data = []);
}
