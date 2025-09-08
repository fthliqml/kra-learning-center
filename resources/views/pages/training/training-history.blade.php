@extends('layouts.app')

@section('content')
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-10
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Module
        </h1>

        <div class="flex gap-2 w-full justify-between lg:justify-end">

            <x-ui.button variant="primary">
                <x-lucide-plus class="size-4" />
                Add
            </x-ui.button>

        </div>


    </div>

    <div class="rounded-lg border border-gray-200 shadow-all p-2">
        <x-table.index>
            <x-table.header>
                <x-table.row type="head">
                    <x-table.head class="!text-center">No</x-table.head>
                    <x-table.head class="w-[300px]">Module Title</x-table.head>
                    <x-table.head class="!text-center">Group Comp</x-table.head>
                    <x-table.head class="!text-center">Duration</x-table.head>
                    <x-table.head class="!text-center">Frequency</x-table.head>
                    <x-table.head class="!text-center">Action</x-table.head>
                </x-table.row>
            </x-table.header>

            <x-table.body>
                @foreach ($modules as $module)
                    <x-table.row>
                        <x-table.cell class="text-center py-4">{{ $module->id }}</x-table.cell>
                        <x-table.cell>{{ $module->title }}</x-table.cell>
                        <x-table.cell class="text-center">{{ $module->group_comp }}</x-table.cell>
                        <x-table.cell class="text-center">{{ $module->duration . ' Hours' }}</x-table.cell>
                        <x-table.cell class="text-center">{{ $module->frequency . ' Days' }}</x-table.cell>
                        <x-table.cell>
                            <div class="flex gap-2 justify-center">
                                <!-- Detail -->
                                <button type="button"
                                    class="text-white hover:opacity-85 p-2 bg-info rounded-lg cursor-pointer"
                                    onclick="">
                                    <x-lucide-eye class="w-4 h-4" />
                                </button>

                                <!-- Edit -->
                                <button type="button"
                                    class="text-gray-700 hover:text-gray-900 hover:opacity-85 p-2 bg-tetriary rounded-lg cursor-pointer"
                                    onclick="">
                                    <x-lucide-edit class="w-4 h-4" />
                                </button>

                                <!-- Delete -->
                                <form method="POST" action="" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-white hover:opacity-85 p-2 bg-danger rounded-lg cursor-pointer">
                                        <x-lucide-trash-2 class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </x-table.cell>

                    </x-table.row>
                @endforeach
            </x-table.body>
        </x-table.index>
    </div>
@endsection
