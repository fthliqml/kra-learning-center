<?php

namespace App\Livewire\Components\Training\Tabs;

use App\Models\Training;
use App\Models\TrainingSession;
use App\Models\TrainingAttendance;
use Livewire\Component;
use Mary\Traits\Toast;
use Carbon\Carbon;

class TrainingAttendanceTab extends Component
{
    use Toast;

    public int $trainingId;
    public int $dayNumber = 1;

    public bool $loading = true;

    // attendance[employee_id] = ['status'=>..., 'remark'=>...]
    public array $attendance = [];
    public array $employees = [];

    public ?Training $training = null;
    public array $sessions = [];

    protected $listeners = [
        'training-day-changed' => 'onDayChanged',
        'training-info-updated' => 'reloadTrainingMeta',
    ];

    public function mount(int $trainingId, int $dayNumber = 1)
    {
        $this->trainingId = $trainingId;
        $this->dayNumber = max(1, (int) $dayNumber);
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loading = true;
        $this->training = Training::with(['sessions.attendances.employee'])->find($this->trainingId);
        if (!$this->training) {
            $this->error('Training not found');
            $this->loading = false;
            return;
        }
        $this->sessions = $this->training->sessions->sortBy('day_number')->values()->toArray();

        // derive employees from all attendance records across sessions
        $employeeMap = [];
        foreach ($this->training->attendances()->with('employee')->get() as $att) {
            if ($att->employee) {
                $employeeMap[$att->employee->id] = [
                    'id' => $att->employee->id,
                    'name' => $att->employee->name,
                    'NRP' => $att->employee->NRP ?? null,
                    'section' => $att->employee->section ?? null,
                ];
            }
        }
        $this->employees = array_values($employeeMap);

        $this->hydrateDayAttendance();
        $this->loading = false;
    }

    private function currentSessionId(): ?int
    {
        $idx = $this->dayNumber - 1;
        return $this->sessions[$idx]['id'] ?? null;
    }

    private function hydrateDayAttendance(): void
    {
        $sessionId = $this->currentSessionId();
        if (!$sessionId)
            return;
        $records = TrainingAttendance::where('session_id', $sessionId)->get();
        $map = [];
        foreach ($records as $r) {
            $map[$r->employee_id] = [
                'status' => $r->status,
                'remark' => $r->notes,
            ];
        }
        $this->attendance = $map;
    }

    public function onDayChanged(int $dayNumber): void
    {
        $this->dayNumber = max(1, $dayNumber);
        $this->hydrateDayAttendance();
    }

    public function reloadTrainingMeta(): void
    {
        // After info updated we may have more sessions (extended date range)
        $this->loadData();
    }

    public function save(): void
    {
        $sessionId = $this->currentSessionId();
        if (!$sessionId)
            return;
        foreach ($this->attendance as $employeeId => $row) {
            if (!$employeeId)
                continue;
            TrainingAttendance::updateOrCreate([
                'session_id' => $sessionId,
                'employee_id' => $employeeId,
            ], [
                'status' => $row['status'] ?? null,
                'notes' => $row['remark'] ?? null,
                'recorded_at' => Carbon::now(),
            ]);
        }
        $this->success('Attendance saved', position: 'toast-top toast-center');
        $this->dispatch('training-attendance-updated', id: $this->trainingId, day: $this->dayNumber);
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="space-y-4 animate-pulse">
            <!-- Header skeleton -->
            <div class="flex justify-between items-start sm:items-center gap-4">
                <div class="h-6 bg-gray-200 rounded w-48"></div>
                <div class="h-8 bg-gray-200 rounded w-32"></div>
            </div>

            <!-- Table skeleton container -->
            <div class="rounded-lg border border-gray-200 shadow">
                <div class="p-2 overflow-x-hidden">
                    <div class="max-h-[400px] overflow-y-auto">
                        <table class="w-full text-sm border-collapse table-fixed">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="py-3 px-2 text-center w-12">
                                        <div class="h-3 w-6 mx-auto bg-gray-200 rounded"></div>
                                    </th>
                                    <th class="py-3 px-2 text-center w-28">
                                        <div class="h-3 w-10 mx-auto bg-gray-200 rounded"></div>
                                    </th>
                                    <th class="py-3 px-3 text-left min-w-[180px]">
                                        <div class="h-3 w-20 bg-gray-200 rounded"></div>
                                    </th>
                                    <th class="py-3 px-2 text-center w-40">
                                        <div class="h-3 w-16 mx-auto bg-gray-200 rounded"></div>
                                    </th>
                                    <th class="py-3 px-2 text-center w-44">
                                        <div class="h-3 w-14 mx-auto bg-gray-200 rounded"></div>
                                    </th>
                                    <th class="py-3 px-2 text-center min-w-[240px]">
                                        <div class="h-3 w-16 mx-auto bg-gray-200 rounded"></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="even:bg-gray-50">
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-5 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-14 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-3"><div class="h-4 w-40 bg-gray-200 rounded"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-24 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-5 w-32 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-52 bg-gray-200 rounded mx-auto"></div></td>
                                </tr>
                                <tr class="even:bg-gray-50">
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-5 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-12 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-3"><div class="h-4 w-44 bg-gray-200 rounded"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-20 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-5 w-28 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-40 bg-gray-200 rounded mx-auto"></div></td>
                                </tr>
                                <tr class="even:bg-gray-50">
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-5 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-10 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-3"><div class="h-4 w-36 bg-gray-200 rounded"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-24 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-5 w-24 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-48 bg-gray-200 rounded mx-auto"></div></td>
                                </tr>
                                <tr class="even:bg-gray-50">
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-5 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-14 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-3"><div class="h-4 w-52 bg-gray-200 rounded"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-20 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-5 w-28 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-56 bg-gray-200 rounded mx-auto"></div></td>
                                </tr>
                                <tr class="even:bg-gray-50">
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-5 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-12 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-3"><div class="h-4 w-40 bg-gray-200 rounded"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-24 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-5 w-24 bg-gray-200 rounded mx-auto"></div></td>
                                    <td class="py-2.5 px-2 text-center"><div class="h-4 w-48 bg-gray-200 rounded mx-auto"></div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Footer skeleton -->
                <div class="px-4 py-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-[11px]">
                    <div class="h-4 w-40 bg-gray-200 rounded"></div>
                    <div class="flex gap-4">
                        <div class="h-4 w-20 bg-gray-200 rounded"></div>
                        <div class="h-4 w-20 bg-gray-200 rounded"></div>
                        <div class="h-4 w-20 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    public function render()
    {
        return view('components.training.tabs.training-attendance-tab');
    }
}
