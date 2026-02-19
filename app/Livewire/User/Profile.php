<?php

namespace App\Livewire\User;

use Livewire\Component;

class Profile extends Component
{
    public $name, $email, $phone;

    public function mount()
    {
        $this->name  = auth()->user()->name;
        $this->email = auth()->user()->email;
        $this->phone = auth()->user()->phone;
    }

    public function save()
    {
        auth()->user()->update([
            'name'  => $this->name,
            'phone' => $this->phone,
        ]);

        session()->flash('success', 'Profile updated successfully');
    }

    public function render()
    {
        return view('livewire.user.profile')->layout('layouts.app');
    }
}
