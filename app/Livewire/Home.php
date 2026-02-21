<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Zone;
use App\Models\Country;

class Home extends Component
{

    public $zones;
    public $compare;
    public $pricingCompare;

    public $countries;


    public function mount()
    {
        $this->zones = Zone::where('status', 'A')
            ->orderBy('position')
            ->get();

        $this->compare = __('compare_table');
        $this->pricingCompare = __('plan_compare_table');

        $this->countries = Country::activeCountries()->get();
    }

    public function render()
    {
        return view('livewire.home')->layout('layouts.app');
    }
}
