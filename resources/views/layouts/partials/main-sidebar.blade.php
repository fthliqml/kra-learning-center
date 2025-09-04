@php
    // menu statis tanpa controller
    $menuItems = [
        ['id' => 'home', 'label' => 'Home', 'icon' => 'home', 'href' => url('/')],
        ['id' => 'courses', 'label' => 'Courses', 'icon' => 'book-text', 'href' => url('/courses')],
        [
            'id' => 'history',
            'label' => 'History',
            'icon' => 'history',
            'href' => '#',
            'submenu' => [
                ['label' => 'Training History', 'href' => url('/history/training')],
                ['label' => 'Certification History', 'href' => url('/history/certification')],
            ],
        ],
        [
            'id' => 'survey',
            'label' => 'Survey',
            'icon' => 'file-text',
            'href' => '#',
            'submenu' => [
                ['label' => 'Survey 1', 'href' => url('/survey/1')],
                ['label' => 'Survey 2', 'href' => url('/survey/2')],
                ['label' => 'Survey 3', 'href' => url('/survey/3')],
            ],
        ],
        ['id' => 'development', 'label' => 'Development', 'icon' => 'goal', 'href' => url('/development')],
    ];

    // segment pertama utk active state (mirip firstSegment di Next)
    $firstSegment = '/' . (request()->segment(1) ?? '');
@endphp

<div x-data="{
    isOpen: false,
    expandedMenus: [],
    toggle(id) {
        if (!this.isOpen) {
            this.isOpen = true
            const i = this.expandedMenus.indexOf(id);
            i > -1 ? this.expandedMenus.splice(i, 1) : this.expandedMenus.push(id);
        } else {
            const i = this.expandedMenus.indexOf(id);
            i > -1 ? this.expandedMenus.splice(i, 1) : this.expandedMenus.push(id);
        }
    },
    has(id) { return this.expandedMenus.includes(id); },
}" class="relative flex transition-all duration-500 ease-in-out"
    :class="isOpen ? 'md:min-w-64' : 'md:min-w-23'">
    <!-- Toggle -->
    <div class="fixed top-10 left-[18px] z-50 flex items-center gap-4">
        <button @click="isOpen = !isOpen"
            class="p-2 rounded-lg bg-white shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 cursor-pointer opacity-60 hover:opacity-100 md:opacity-100"
            :class="isOpen && 'opacity-100'">
            <x-lucide-menu class="w-5 h-5 text-primary" />
        </button>

        <!-- Logo -->
        <h1 class="text-4xl font-bold transform transition-all duration-500 hidden md:block"
            :class="isOpen ? 'translate-x-0 opacity-100' : '-translate-x-10 opacity-0 pointer-events-none'">
            <span class="bg-gradient-to-r from-primary to-primary text-transparent bg-clip-text">K</span>
            <span class="text-primary">-</span>
            <span class="bg-gradient-to-r from-primary to-tetriary text-transparent bg-clip-text">LEARN</span>
        </h1>
    </div>

    <!-- Sidebar -->
    <div class="fixed left-0 z-50 rounded-tr-[80px] transition-all duration-500 ease-in-out bg-gradient-to-b from-primary to-secondary"
        :class="isOpen
            ?
            'w-64 h-[85vh] top-[15vh] translate-x-0' :
            'w-23 h-80 top-[15vh] rounded-br-[60px] rounded-tr-[60px] -translate-x-full md:translate-x-0'">
        <div class="flex flex-col h-full p-4 pr-[24px]" :class="!isOpen && 'pl-[10px] pr-[30px]'">

            <!-- Nav -->
            <nav class="flex-1 space-y-2 transition-all duration-1000 ease-out"
                :class="isOpen ? 'mt-[11px]' : 'mt-4 space-y-3'">

                @foreach ($menuItems as $item)
                    @php
                        $hasSub = isset($item['submenu']) && count($item['submenu']) > 0;
                        $isActiveTop = !$hasSub && $firstSegment === parse_url($item['href'], PHP_URL_PATH);
                    @endphp

                    <div class="transition-all duration-1000 ease-out">
                        <!-- Main item -->
                        <button
                            @click="{{ $hasSub ? "toggle('{$item['id']}')" : "window.location.href='{$item['href']}'" }}"
                            class="group flex items-center justify-between w-full px-3 py-2 rounded-md text-left text-white hover:bg-white/10 transition"
                            :class="{
                                'bg-white text-primary': {{ $isActiveTop ? 'true' : 'false' }},
                            }"
                            @keydown.enter.prevent="{{ $hasSub ? "toggle('{$item['id']}')" : '' }}"
                            aria-expanded="{{ $hasSub ? 'true' : 'false' }}">
                            <span class="flex items-center gap-3">
                                <x-dynamic-component :component="'lucide-' . $item['icon']" class="w-5 h-5" />
                                <span x-show="isOpen" x-transition>{{ $item['label'] }}</span>
                            </span>

                            @if ($hasSub)
                                <!-- Chevron/arrow muncul hanya saat terbuka -->
                                <x-lucide-chevron-down x-show="isOpen" class="w-4 h-4 transition-transform duration-300"
                                    :class="'rotate-0 w-4 h-4 transition-transform duration-300'"
                                    x-bind:class="has('{{ $item['id'] }}') ? 'rotate-180' : 'rotate-0'" />
                            @endif
                        </button>

                        <!-- Submenu -->
                        @if ($hasSub)
                            <div class="ml-6 overflow-hidden transition-all ease-out"
                                x-show="has('{{ $item['id'] }}') && isOpen"
                                x-transition:enter="transition duration-500 ease-out"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition duration-300 ease-in"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0 -translate-y-2">
                                @foreach ($item['submenu'] as $index => $sub)
                                    @php
                                        $subActive =
                                            request()->fullUrlIs($sub['href']) || url()->current() === $sub['href'];
                                    @endphp
                                    <button @click="window.location.href='{{ $sub['href'] }}'"
                                        class="block w-full text-left px-3 py-2 text-sm rounded-md transition
                                               text-white/80 hover:text-white hover:bg-white/5
                                               {{ $subActive ? 'bg-white text-primary hover:text-primary' : '' }}"
                                        style="transition-delay: {{ $index * 50 }}ms">
                                        {{ $sub['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

            </nav>
        </div>
    </div>

    <!-- Overlay (mobile) -->
    <div class="fixed inset-0 bg-black/50 z-40 transition-opacity duration-300 md:hidden" x-show="isOpen"
        @click="isOpen = false" x-transition.opacity></div>
</div>
