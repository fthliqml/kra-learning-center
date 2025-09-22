 <div>
     <!-- Month Navigation -->
     <div class="flex items-center justify-center mb-4">
         <button wire:click="previousMonth" class="p-2 hover:bg-gray-100 rounded-full cursor-pointer">
             <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
             </svg>
         </button>
         <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mx-4">{{ $monthName }}</h2>
         <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 rounded-full cursor-pointer">
             <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
             </svg>
         </button>
     </div>

     <!-- Calendar Grid -->
     <div class="bg-white rounded-lg overflow-hidden border border-gray-200 shadow">
         <!-- Day Headers -->
         <div class="grid grid-cols-7" style="background: linear-gradient(to right, #4863a0, #123456, #4863a0)">
             <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">MON
             </div>
             <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">TUE
             </div>
             <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">WED
             </div>
             <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">THU
             </div>
             <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">FRI
             </div>
             <div class="p-1 sm:p-4 text-center text-white font-medium border-r border-white text-xs sm:text-base">SAT
             </div>
             <div class="p-1 sm:p-4 text-center text-white font-medium text-xs sm:text-base">SUN</div>
         </div>

         <!-- Calendar Days -->
         <div class="grid grid-cols-7">
             @foreach ($days as $day)
                 <div @class([
                     'border-b border-gray-200 relative flex flex-col border-r',
                     'h-[60px] sm:min-h-[200px]',
                     'bg-gray-50' => !$day['isCurrentMonth'],
                     'bg-white' => $day['isCurrentMonth'],
                 ])>
                     <!-- Date Number -->
                     <div class="flex justify-between items-start p-0.5 sm:p-2">
                         <span
                             class="font-medium text-[8px] sm:text-base w-3 h-3 sm:w-8 sm:h-8 {{ $day['isToday'] ? 'bg-primary text-white rounded-full flex items-center justify-center' : ($day['isCurrentMonth'] ? 'text-gray-900' : 'text-gray-400') }}">
                             {{ $day['date']->format('j') }}
                         </span>
                     </div>

                     <!-- Multiple Training Events dengan layering system -->
                     @if (!empty($day['trainings']))
                         <div @class([
                             'px-0.5 sm:px-1 mb-0.5 sm:mb-2 flex-1 flex flex-col gap-0.5 sm:gap-1',
                             'overflow-y-auto' => count($day['trainings']) > 5,
                         ])>
                             @foreach ($day['trainings'] as $training)
                                 <div wire:click="openEventModal({{ $training['id'] }})" @class([
                                     'bg-[#C0E4FF] hover:bg-[#A8D5FF] cursor-pointer transition-colors duration-200',
                                     'py-0.5 px-0.5 text-[7px] h-2.5 sm:py-1 sm:px-2 sm:text-xs sm:h-6 flex-shrink-0',
                                     'border-l-[1px] sm:border-l-4 border-tetriary rounded-sm sm:rounded-md',
                                 ])>
                                     <div class="font-medium text-black leading-2 sm:leading-4 truncate">
                                         {{ $training['name'] }}
                                     </div>
                                 </div>
                             @endforeach
                         </div>
                     @endif
                 </div>
             @endforeach
         </div>
     </div>

     @isset($selectedEvent)
         <!-- Modal for Event Details -->
         <x-modal wire:model="modal" icon='o-document-text' title="Training Details"
             subtitle="Information about training details" separator box-class="max-w-4xl h-fit">
             <div
                 class="grid grid-cols-1 sm:grid-cols-2 gap-5 justify-items-center items-center border-b border-gray-400 pb-5">
                 <x-card body-class="flex justify-center items-center gap-5 relative group"
                     class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#FF6B6B] hover:bg-gray-100 cursor-pointer transition-colors"
                     wire:click="$toggle('editModes.training_name')" separator>

                     <x-icon name="o-document-text" class="w-7 h-7 text-[#FF6B6B]" />

                     <div class="flex-1">
                         <span class="text-sm text-gray-700">Training Name</span>

                         @if ($editModes['training_name'])
                             <x-input type="text" wire:model="selectedEvent.name" wire:click.stop
                                 class="rounded-none border-t-0 border-x-0 border-b-primary focus:outline-none focus-within:outline-none px-0 bg-transparent w-full mr-10" />
                         @else
                             <h1 class="font-semibold cursor-pointer">
                                 {{ $selectedEvent['name'] ?? '-' }}
                             </h1>
                         @endif
                     </div>

                     <x-icon name="{{ $editModes['training_name'] ? 'o-check' : 'o-pencil' }}"
                         class="w-5 h-5 text-gray-400 absolute top-3.5 right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                         wire:click="$toggle('editModes.training_name')" />

                 </x-card>


                 <x-card body-class="flex justify-center items-center gap-5 relative group"
                     class="shadow border border-gray-200 w-full border-l-[6px] border-l-primary hover:bg-gray-100 cursor-pointer transition-colors"
                     wire:click="$toggle('editModes.date')" separator>

                     <x-icon name="o-calendar-date-range" class="w-7 h-7 text-primary" />

                     <div class="flex-1">
                         <span class="text-sm text-gray-700">Training Date</span>

                         @if ($editModes['date'])
                             <div onclick="event.stopPropagation()">
                                 <x-datepicker wire:model.defer="trainingDateRange" placeholder="Select date range"
                                     icon="o-calendar" :config="$config = [
                                         'mode' => 'range',
                                         'altInput' => true,
                                         'altFormat' => 'd M Y',
                                     ]" class="w-full bg-transparent" />
                             </div>
                         @else
                             <h1 class="font-semibold">
                                 {{ formatRangeDate($selectedEvent['start_date'], $selectedEvent['end_date']) }}
                             </h1>
                         @endif
                     </div>

                     <x-icon name="{{ $editModes['date'] ? 'o-check' : 'o-pencil' }}"
                         class="w-5 h-5 text-gray-400 absolute {{ $editModes['date'] ? 'top-0' : 'top-3.5' }} right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                         wire:click="$toggle('editModes.date')" />
                 </x-card>



                 <x-card body-class="flex justify-center items-center gap-5 relative group"
                     class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#6A4C93] hover:bg-gray-100 cursor-pointer"
                     wire:click="$toggle('editModes.trainer')" separator>

                     <x-icon name="o-user" class="w-7 h-7 text-[#6A4C93]" />

                     <div class="flex-1">
                         <span class="text-sm text-gray-700">Instructor</span>

                         @if ($editModes['trainer'])
                             <div onclick="event.stopPropagation()">

                                 <x-select wire:model="trainer.id" :options="$trainers" option-label="user.name"
                                     option-value="id" class="w-full focus-within:outline-none" />
                             </div>
                         @else
                             <h1 class="font-semibold">
                                 {{ $trainer['name'] ?? '-' }}
                             </h1>
                         @endif
                     </div>

                     <x-icon name="{{ $editModes['trainer'] ? 'o-check' : 'o-pencil' }}"
                         class="w-5 h-5 text-gray-400 absolute {{ $editModes['trainer'] ? 'top-0' : 'top-3.5' }} right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                         wire:click="$toggle('editModes.trainer')" />
                 </x-card>


                 <x-card body-class="flex justify-center items-center gap-5 relative group"
                     class="shadow border border-gray-200 w-full border-l-[6px] border-l-[#4CAF50] hover:bg-gray-100 cursor-pointer transition-colors"
                     wire:click="$toggle('editModes.location')" separator>

                     <x-icon name="o-map-pin" class="w-7 h-7 text-[#4CAF50]" />

                     <div class="flex-1">
                         <span class="text-sm text-gray-700">Location</span>

                         @if ($editModes['location'])
                             <div class="flex gap-5">
                                 <x-input type="text" wire:model="sessions.{{ $dayNumber - 1 }}.room_name"
                                     wire:click.stop label="Name"
                                     class="rounded-none border-t-0 border-x-0 border-b-primary focus:outline-none focus-within:outline-none w-full bg-transparent px-0" />

                                 <x-input type="text" wire:model="sessions.{{ $dayNumber - 1 }}.room_location"
                                     wire:click.stop label="Office"
                                     class="rounded-none border-t-0 border-x-0 border-b-primary focus:outline-none focus-within:outline-none w-full bg-transparent px-0" />
                             </div>
                         @else
                             <h1 class="font-semibold">
                                 {{ $this->currentSession['room_name'] ?? '' }} -
                                 {{ $this->currentSession['room_location'] ?? '' }}
                             </h1>
                         @endif
                     </div>

                     <x-icon name="{{ $editModes['location'] ? 'o-check' : 'o-pencil' }}"
                         class="w-5 h-5 text-gray-400 absolute top-3.5 right-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                         wire:click="$toggle('editModes.location')" />

                 </x-card>

             </div>

             <div class="mt-5">
                 <div class="flex justify-between flex-col md:flex-row items-start md:items-center gap-3 md:gap-0 mb-5">
                     <h2 class="text-xl font-bold">Attendance List</h2>
                     <div class="flex gap-2">
                         <x-select wire:model="dayNumber" wire:change="$refresh" :options="$trainingDates" />
                     </div>
                 </div>

                 <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-y-auto h-[300px]">
                     <x-table :headers="[
                         ['key' => 'no', 'label' => 'No', 'class' => 'text-center'],
                         ['key' => 'NRP', 'label' => 'NRP', 'class' => 'text-center'],
                         ['key' => 'name', 'label' => 'Name', 'class' => 'w-[250px] text-center'],
                         ['key' => 'section', 'label' => 'Section', 'class' => 'text-center'],
                         ['key' => 'attendance', 'label' => 'Attendance', 'class' => 'text-center'],
                         ['key' => 'remark', 'label' => 'Remark', 'class' => 'text-center'],
                     ]" :rows="$employees" striped>

                         {{-- Custom cell No --}}
                         @scope('cell_no', $row)
                             {{ $loop->iteration }}
                         @endscope

                         {{-- Custom cell Attendance --}}
                         @scope('cell_attendance', $row)
                             <x-select wire:model="attendances.{{ $this->dayNumber }}.{{ $row->id }}.status"
                                 :options="[
                                     ['id' => 'present', 'name' => 'Present'],
                                     ['id' => 'absent', 'name' => 'Absent'],
                                     ['id' => 'pending', 'name' => 'Pending'],
                                 ]" class="!w-28 !h-8 text-sm" />
                         @endscope

                         {{-- Custom cell Remark --}}
                         @scope('cell_remark', $row)
                             <x-input wire:model="attendances.{{ $this->dayNumber }}.{{ $row->id }}.remark"
                                 placeholder="" class="!w-40 !h-8 text-sm" />
                         @endscope

                     </x-table>
                 </div>
                 <div class="flex justify-between items-center mt-5">
                     <x-button @click="$wire.modal = false" class="btn btn-error bg-white hover:bg-error">
                         Cancel
                     </x-button>
                     <x-button wire:click="updateAttendance" icon="o-bookmark" class="btn btn-success"
                         spinner="updateAttendance"> Save
                     </x-button>
                 </div>
             </div>
         </x-modal>
     @endisset
 </div>
