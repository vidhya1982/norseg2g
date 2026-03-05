<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Zone;
class Plans extends Component
{ 
    public $zones;
    public $is_unlimited;

    public function mount()
    {
        $this->zones = Zone::where('status', 'A')
            ->orderBy('position')
            ->get();

         $this->is_unlimited = Zone::where('status', 'A')
            ->whereHas('plans', function($q){
                    $q->where('is_unlimited',1);
            })
            ->orderBy('position')
            ->get();
    }
    public function render()
    {
        return view('livewire.plans')->layout('layouts.app');
    }
}
