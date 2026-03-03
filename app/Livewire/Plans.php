<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Zone;
class Plans extends Component
{ 
    public $zones;

    public function mount()
    {
        $this->zones = Zone::where('status', 'A')
            ->orderBy('position')
            ->get();
    }
    public function render()
    {
        return view('livewire.plans')->layout('layouts.app');
    }
}
