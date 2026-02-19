<?php

namespace App\Livewire;

use Livewire\Component;

class Business extends Component
{
    public function render()
    {
        return view('livewire.business')->layout('layouts.app');
    }
}
