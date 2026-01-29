<?php

namespace App\Livewire\Pages\Reports;

use App\Exports\InstructorDailyRecordExport;
use App\Models\InstructorDailyRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

class InstructorDailyRecordReport extends Component
{
    use Toast, WithPagination;

    public $search = '';
    public $dateRange = null;

    public function mount(): void
    {
        $this->dateRange = null;
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'dateRange']);
        $this->resetPage();
    }

    /**
     * Refresh data when a record is saved
     */
    #[On('record-saved')]
    public function refreshData(): void
    {
        // Simply re-render the component to refresh data
    }

    /**
     * Parse date range string into from and to dates
     */
    protected function parseDateRange(): array
    {
        if (!$this->dateRange) {
            return [null, null];
        }

        $dates = explode(' to ', $this->dateRange);
        $from = isset($dates[0]) ? trim($dates[0]) : null;
        $to = isset($dates[1]) ? trim($dates[1]) : $from;

        return [$from, $to];
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'dateRange'])) {
            $this->resetPage();
        }
    }

    /**
     * Check if current user can see all records (admin/leader)
     */
    protected function canSeeAllRecords(): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        // Admin role from user_roles table
        if ($user->hasRole('admin')) {
            return true;
        }

        // Leader position (section_head, department_head, division_head, director)
        $leaderPositions = ['section_head', 'department_head', 'division_head', 'director'];
        if (in_array($user->position, $leaderPositions)) {
            return true;
        }

        return false;
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-[50px]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center w-[100px]'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-[150px] text-center'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-center w-[120px]'],
            ['key' => 'code', 'label' => 'Code', 'class' => '!text-center w-[120px]'],
            ['key' => 'activity', 'label' => 'Activity', 'class' => 'w-[250px] text-center'],
            ['key' => 'remarks', 'label' => 'Remarks', 'class' => 'w-[180px] text-center'],
            ['key' => 'hour', 'label' => 'Hour', 'class' => '!text-center w-[80px]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center w-[100px]'],
        ];
    }

    public function records()
    {
        $user = Auth::user();
        [$dateFrom, $dateTo] = $this->parseDateRange();

        $query = InstructorDailyRecord::with('instructor')
            ->when(!$this->canSeeAllRecords(), fn($q) => $q->where('instructor_id', $user->id))
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('activity', 'like', '%' . $this->search . '%')
                        ->orWhere('remarks', 'like', '%' . $this->search . '%')
                        ->orWhereHas('instructor', function ($iq) {
                            $iq->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('nrp', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $paginator = $query->paginate(10)->onEachSide(1);

        return $paginator->through(function ($record, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $record->no = $start + $index;
            $record->nrp = $record->instructor->nrp ?? '-';
            $record->name = $record->instructor->name ?? '-';
            $record->formatted_date = $record->date ? Carbon::parse($record->date)->format('d-m-Y') : '-';
            $record->formatted_hour = number_format($record->hour, 1);
            return $record;
        });
    }

    #[On('delete-record')]
    public function deleteRecord($id): void
    {
        $user = Auth::user();
        $record = InstructorDailyRecord::find($id);

        if (!$record) {
            $this->error('Record not found.', position: 'toast-top toast-center');
            $this->dispatch('confirm-done');
            return;
        }

        // Check permission: instructor can only delete own records
        if (!$this->canSeeAllRecords() && $record->instructor_id !== $user->id) {
            $this->error('You are not authorized to delete this record.', position: 'toast-top toast-center');
            $this->dispatch('confirm-done');
            return;
        }

        $record->delete();
        $this->success('Record deleted successfully.', position: 'toast-top toast-center');
        $this->dispatch('confirm-done');
    }

    public function export()
    {
        $user = Auth::user();
        [$dateFrom, $dateTo] = $this->parseDateRange();
        $instructorId = $this->canSeeAllRecords() ? null : $user->id;

        return Excel::download(
            new InstructorDailyRecordExport($dateFrom, $dateTo, $this->search, $instructorId),
            'instructor_daily_record_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function render()
    {
        return view('pages.reports.instructor-daily-record', [
            'records' => $this->records(),
            'headers' => $this->headers(),
        ]);
    }
}
