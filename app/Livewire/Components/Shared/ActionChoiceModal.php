<?php

namespace App\Livewire\Components\Shared;

use Livewire\Component;
use Mary\Traits\Toast;

class ActionChoiceModal extends Component
{
    use Toast;

    public bool $show = false;

    // Context data (e.g., training id, type, etc.) passed when opening
    public array $payload = [];

    // Modal display properties
    public string $title = 'Select Action';
    public string $message = 'Choose what you want to do.';

    // Actions: [['label' => 'View Detail', 'event' => 'open-training-detail', 'variant' => 'outline']]
    public array $actions = [];

    protected $listeners = [
        'open-action-choice' => 'open'
    ];

    public function open(array $config): void
    {
        $this->resetErrorBag();
        $this->title = $config['title'] ?? 'Select Action';
        $this->message = $config['message'] ?? 'Choose what you want to do.';
        $this->actions = $config['actions'] ?? [];
        $this->payload = $config['payload'] ?? [];
        $this->show = true;
    }

    public function choose(string $event): void
    {
        if (empty($event)) {
            $this->error('Invalid action');
            return;
        }
        // Forward the payload with selected action
        $this->dispatch($event, $this->payload);
        $this->show = false;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('components.shared.action-choice-modal');
    }
}
