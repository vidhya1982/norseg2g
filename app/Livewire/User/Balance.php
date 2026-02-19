<?php

namespace App\Livewire\User;

use Livewire\Component;

class Balance extends Component
{
    public function render()
    {
        return view('livewire.user.balance')->layout('layouts.app');
    }
}
