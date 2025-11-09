<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;

class ConfirmDialog extends Component
{
    public $show = false;
    public $title;
    public $text;
    public $action;
    public $id;
    public $processing = false;

    #[On('confirm')]
    public function confirm($title = 'Konfirmasi', $text = 'Apakah Anda yakin?', $action = null, $id = null)
    {
        $this->title = $title;
        $this->text = $text;
        $this->action = $action;
        $this->id = $id;

        $this->processing = false;
        $this->show = true;
    }
    public function proceed()
    {
        $this->processing = true;
        if ($this->action) {
            $this->dispatch($this->action, $this->id);
        }
        // wait for 'confirm-done' event to close
    }

    public function cancel()
    {
        $this->resetDialog();
    }

    private function resetDialog()
    {
        $this->reset(['show', 'title', 'text', 'action', 'id', 'processing']);
    }

    #[On('confirm-done')]
    public function done()
    {
        $this->resetDialog();
    }

    public function render()
    {
        return view('components.confirm-dialog');
    }
}
