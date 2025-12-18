<?php

/**
 * IDE helper for Livewire's view macros.
 *
 * Livewire registers `layout()` / `layoutData()` macros on the View instance at runtime,
 * but static analyzers (e.g., Intelephense) don't see them.
 *
 * This file is NOT autoloaded by Composer, so it has zero runtime impact.
 */

namespace Illuminate\View {
    if (false) {
        /**
         * @method $this layout(string $layout, array $data = [])
         * @method $this layoutData(array $data = [])
         */
        class View {}
    }
}
