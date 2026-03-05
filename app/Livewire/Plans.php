<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Plans as PlanModel;
use App\Models\Zone;

class Plans extends Component
{
    public $zone;
    public $unlimitedPlans;
    public $budgetPlans;

public function mount()
{
    $this->zone = Zone::find(1);

    // Unlimited plans (unique by Days)
    $this->unlimitedPlans = PlanModel::where('zone_id',1)
        ->where('is_unlimited',1)
        ->whereIn('Days',[5,10,21])
        ->selectRaw('MIN(id) as id, Days, MIN(USD) as USD')
        ->groupBy('Days')
        ->orderByRaw("FIELD(Days,5,10,21)")
        ->get();

    // Budget plans (unique by GB)
    $this->budgetPlans = PlanModel::where('zone_id',1)
        ->where('is_unlimited',0)
        ->whereIn('GB',[3,5,10,20])
        ->selectRaw('MIN(id) as id, GB, MIN(Days) as Days')
        ->groupBy('GB')
        ->orderByRaw("FIELD(GB,3,5,10,20)")
        ->get();
}
    public function render()
    {
        return view('livewire.plans')->layout('layouts.app');
    }
}
