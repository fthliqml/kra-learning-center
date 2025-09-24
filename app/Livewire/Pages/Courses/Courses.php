<?php

namespace App\Livewire\Pages\Courses;

use Livewire\Component;

class Courses extends Component
{
    public $groupOptions = [
        ['value' => 'all', 'label' => 'All Groups'],
        ['value' => 'group1', 'label' => 'Group 1'],
        ['value' => 'group2', 'label' => 'Group 2'],
    ];

    public function render()
    {
        return view('pages.courses.courses');
    }
}
