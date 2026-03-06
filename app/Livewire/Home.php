<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Plans as PlanModel;
use App\Models\Zone;
use App\Models\Country;

class Home extends Component
{

    public $zone;
    public $compare;
    public $pricingCompare;

    public $countries;
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
  $this->budgetPlans = PlanModel::where('zone_id', 1)
    ->where('is_unlimited', 0)
    ->where('USD', '>', 0)   // ✅ free esim hata diya
    ->whereIn('GB', [3, 5, 10, 20])
    ->selectRaw('MIN(id) as id, GB, MIN(Days) as Days, MIN(USD) as USD')
    ->groupBy('GB')
    ->orderByRaw("FIELD(GB,3,5,10,20)")
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
