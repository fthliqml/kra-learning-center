<?php

namespace App\Livewire\Components\TrainingSchedule;

use Livewire\Component;
use Mary\Traits\Toast;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrainingExport;
use App\Exports\TrainingTemplateExport;
use App\Imports\TrainingImport;
use Illuminate\Validation\ValidationException;

class ScheduleExcelActions extends Component
{
    use Toast;

    public function export()
    {
        return response()->streamDownload(function () {
            echo Excel::raw(new TrainingExport(), \Maatwebsite\Excel\Excel::XLSX);
        }, 'trainings.xlsx');
    }

    public function downloadTemplate()
    {
        return response()->streamDownload(function () {
            echo Excel::raw(new TrainingTemplateExport(), \Maatwebsite\Excel\Excel::XLSX);
        }, 'training_template.xlsx');
    }

    public function openImport(): void
    {
        $this->dispatch('open-training-import');
    }

    public function render()
    {
        return view('components.training-schedule.schedule-excel-actions');
    }
}
