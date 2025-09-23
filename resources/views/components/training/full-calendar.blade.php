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
                     'border-b border-gray-200 relative flex flex-col border-r h-[60px] sm:min-h-[200px] hover:bg-gradient-to-br hover:from-gray-100 hover:to-gray-200
                                                                                                                               hover:border-gray-300 hover:shadow-lg
                                                                                                                                   transform hover:-translate-y-0.5
                                                                                                                                   transition-all duration-200 ease-in-out
                                                                                                                                   cursor-pointer',
                     'bg-gray-50' => !$day['isCurrentMonth'],
                     'bg-white' => $day['isCurrentMonth'],
                 ]) wire:click="openAddTrainingModal('{{ $day['date'] }}')">

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
                                 <div wire:click.stop="openEventModal({{ $training['id'] }})"
                                     @class([
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

     {{-- Detail modal moved to <livewire:components.training.detail-training-modal /> --}}
 </div>
