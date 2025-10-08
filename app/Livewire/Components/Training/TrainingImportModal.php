<?php

namespace App\Livewire\Components\Training;

use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class TrainingImportModal extends Component
{
    use WithFileUploads, Toast;

    public bool $show = false;
    public $file;
    public bool $fileReady = false;

    protected $listeners = [
        'open-training-import' => 'open',
    ];

    public function open(): void
    {
        $this->reset(['file', 'fileReady']);
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->reset(['file', 'fileReady']);
    }

    public function import(): void
    {
        if (!$this->file) {
            $this->error('Please select an Excel file first', position: 'toast-top toast-center');
            return;
        }
        try {
            $this->validate([
                'file' => 'required|file|mimes:xlsx,xls'
            ], [
                'file.required' => 'File is required.',
                'file.mimes' => 'The file must be an Excel file (.xlsx / .xls).'
            ]);

            // Gunakan langsung temporary uploaded file (lebih stabil di Livewire) tanpa memindahkan terlebih dulu
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\TrainingImport(), $this->file->getRealPath());
            $this->success('Training data imported successfully', position: 'toast-top toast-center');
            $this->dispatch('training-created');
            $this->close();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $fileErrors = $e->validator->errors()->get('file');
            if ($fileErrors) {
                if (count($fileErrors) === 1) {
                    $this->error($fileErrors[0], position: 'toast-top toast-center');
                } else {
                    $msg = collect($fileErrors)->map(fn($m) => '• ' . $m)->implode('<br>');
                    $this->error($msg, position: 'toast-top toast-center');
                }
            }
            $importErrorsAll = $e->errors()['import'] ?? [];
            $flat = collect($importErrorsAll)->take(15);
            if ($flat->isNotEmpty()) {
                if (count($importErrorsAll) === 1) {
                    $this->error($flat->first(), position: 'toast-top toast-center');
                } else {
                    $msg = $flat->map(fn($m) => '• ' . $m)->implode('<br>');
                    if (count($importErrorsAll) > 15) {
                        $msg .= '<br>• (' . (count($importErrorsAll) - 15) . ' more ...)';
                    }
                    $this->error($msg, position: 'toast-top toast-center');
                }
            }
        } catch (\Throwable $t) {
            $this->error('Unexpected error: ' . $t->getMessage(), position: 'toast-top toast-center');
        } finally {
            $this->reset(['file', 'fileReady']);
        }
    }

    public function updatedFile(): void
    {
        $this->fileReady = false;
        if (!$this->file)
            return;
        try {
            $this->validate([
                'file' => 'required|file|mimes:xlsx,xls'
            ], [
                'file.required' => 'File is required.',
                'file.mimes' => 'The file must be an Excel file (.xlsx / .xls).'
            ]);
            // File is valid; set flag so UI can display readiness without toast
            $this->fileReady = true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $fileErrors = $e->validator->errors()->get('file');
            if ($fileErrors) {
                if (count($fileErrors) === 1) {
                    $this->error($fileErrors[0], position: 'toast-top toast-center');
                } else {
                    $msg = collect($fileErrors)->map(fn($m) => '• ' . $m)->implode('<br>');
                    $this->error($msg, position: 'toast-top toast-center');
                }
            }
            $this->reset(['file', 'fileReady']);
        }
    }

    public function render()
    {
        return view('components.training.training-import-modal');
    }
}
