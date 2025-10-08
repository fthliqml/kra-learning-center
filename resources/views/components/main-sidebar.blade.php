@php
    use App\Support\SidebarMenu;
    $user = auth()->user();
    $role = $user?->role;
    $menuItems = SidebarMenu::for($user);
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
            <x-icon name="o-bars-3" class="w-5 h-5 text-primary" />
        </button>

        <!-- Logo: only rendered (display:block) when sidebar open to avoid overlaying tab headers -->
        @php
            $manageRoles = ['admin', 'instructor', 'leader'];
            $logoMode = in_array(strtolower($role ?? ''), $manageRoles) ? 'MANAGE' : 'LEARN';
        @endphp
        <h1 x-cloak x-show="isOpen" x-transition:enter="transition ease-out duration-400"
            x-transition:enter-start="opacity-0 -translate-x-6" x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-6" class="text-4xl font-bold hidden md:block select-none">
            <span class="bg-gradient-to-r from-primary to-primary text-transparent bg-clip-text">K</span>
            <span class="text-primary">-</span>
            <span
                class="bg-gradient-to-r from-primary to-tetriary text-transparent bg-clip-text">{{ $logoMode }}</span>
        </h1>
    </div>

    <!-- Sidebar -->
    <div class="fixed left-0 z-50 rounded-tr-[80px] transition-all duration-500 ease-in-out bg-gradient-to-b from-primary to-secondary"
        :class="isOpen
            ?
            'w-64 h-[85vh] top-[15vh] translate-x-0' :
            'w-23 h-fit top-[15vh] rounded-br-[60px] !rounded-tr-[60px] -translate-x-full md:translate-x-0'">
        <div class="flex flex-col h-full p-4 pr-[24px]" :class="!isOpen && 'pl-[10px] pr-[30px]'">

            <!-- Nav -->
            <nav class="flex-1 space-y-2 transition-all duration-1000 ease-out"
                :class="isOpen ? 'mt-[15px]' : 'space-y-3'">

                @foreach ($menuItems as $item)
                    @php
                        $hasSub = isset($item['submenu']) && count(value: $item['submenu']) > 0;
                        $path = ltrim(parse_url($item['href'], PHP_URL_PATH) ?? '/', '/');
                        // Active only when exact path matches (no wildcard) for top-level items without submenu
                        $currentPath = trim(request()->path(), '/');
                        $isActiveTop =
                            !$hasSub &&
                            (($path === '' && $currentPath === '') || ($path !== '' && $currentPath === $path));
                    @endphp

                    <div class="transition-all duration-1000 ease-out">
                        <!-- Main item -->
                        <button
                            @click="{{ $hasSub ? "toggle('{$item['id']}')" : "window.location.href='{$item['href']}'" }}"
                            @class([
                                'group flex w-full px-3 py-2 text-left transition-all cursor-pointer',
                                'rounded-md',
                                'bg-white text-primary' => $isActiveTop,
                                'text-white hover:bg-white/10 hover:text-white' => !$isActiveTop,
                            ])
                            :class="{
                                'justify-between': isOpen,
                                'justify-center': !isOpen,
                                'rounded-tr-[80px]': isOpen && '{{ $item['id'] }}'
                                === 'home'
                            }"
                            @keydown.enter.prevent="{{ $hasSub ? "toggle('{$item['id']}')" : '' }}"
                            aria-expanded="{{ $hasSub ? 'true' : 'false' }}">

                            <span class="flex items-center gap-3">
                                <x-icon :name="'o-' . $item['icon']" class="w-[23px] h-[23px]" />
                                <span x-show="isOpen" x-transition>{{ $item['label'] }}</span>
                            </span>

                            @if ($hasSub)
                                <x-icon name="o-chevron-down" x-show="isOpen"
                                    class="w-4 h-4 transition-transform duration-300" :class="'rotate-0 w-4 h-4 transition-transform duration-300'"
                                    x-bind:class="has('{{ $item['id'] }}') ? 'rotate-180' : 'rotate-0'" />
                            @endif
                        </button>

                        <!-- Submenu -->
                        @if ($hasSub)
                            <div class="ml-6 overflow-hidden transition-all ease-out mt-2"
                                x-show="has('{{ $item['id'] }}') && isOpen"
                                x-transition:enter="transition duration-500 ease-out"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition duration-300 ease-in"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0 -translate-y-2">
                                @foreach ($item['submenu'] as $index => $sub)
                                    @php
                                        // Determine active state for submenu by comparing normalized paths (ignoring domain & query)
                                        $subPath = ltrim(parse_url($sub['href'], PHP_URL_PATH) ?? '/', '/');
                                        $currentPath = trim(request()->path(), '/');
                                        $subActive =
                                            ($subPath === '' && $currentPath === '') ||
                                            ($subPath !== '' && $currentPath === $subPath);
                                    @endphp
                                    <button @click="window.location.href='{{ $sub['href'] }}'"
                                        @class([
                                            'block w-full text-left px-3 py-2 text-sm rounded-md transition cursor-pointer',
                                            'hover:bg-white/5 text-white/80 hover:text-white' => !$subActive,
                                            'bg-white text-primary hover:text-primary hover:opacity-80' => $subActive,
                                        ]) style="transition-delay: {{ $index * 50 }}ms">
                                        {{ $sub['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

            </nav>
            @auth
                <div class="mt-4 pt-4 border-t border-white/20" x-show="isOpen" x-transition>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                            class="group flex items-center gap-3 w-full px-3 py-2 rounded-md bg-white/10 hover:cursor-pointer hover:bg-white/20 text-white text-sm transition-all">
                            <x-icon name="o-arrow-left-on-rectangle" class="w-[20px] h-[20px]" />
                            <span x-show="isOpen" x-transition>Logout</span>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </div>

    <!-- Overlay (mobile) -->
    <div class="fixed inset-0 bg-black/50 z-40 transition-opacity duration-300 md:hidden" x-show="isOpen"
        @click="isOpen = false" x-transition.opacity></div>
</div>
