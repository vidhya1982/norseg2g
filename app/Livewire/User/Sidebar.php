<?php

namespace App\Livewire\User;

use Livewire\Component;

class Sidebar extends Component
{
    public function logout()
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.user.sidebar');
    }
}

