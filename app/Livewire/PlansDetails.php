<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Plans;
use App\Models\Zone;
use App\Models\Country;
use App\Services\CartService;

class PlansDetails extends Component
{
    public $zone;
    public $countries;

    public $plans;

    public $selectedPlanId;
    public $quantity = 1;
public bool $hasBonusPromo = false;
public bool $hasBOGOPromo  = false;
public string $promoBannerText = '';
    public $addons = [
        'talk_time' => [
            'enabled' => false,  // ✅ correct default
            'qty' => 1,
            'price' => 10
        ],
        'auto_topup' => [
            'enabled' => false
        ]
    ];

    public function mount(Zone $zone)
{
   $this->hasBonusPromo = session('pending_promo') === 'NORSETEST';
    $this->hasBOGOPromo  = session('pending_promo') === 'NORSEBOGO';

    if ($this->hasBonusPromo) {
        $this->promoBannerText = '+2GB bonus data will be added to this plan on activation!';
    }
    if ($this->hasBOGOPromo) {
        $this->promoBannerText = 'Buy 1 Get 1 Free! Add this plan — you\'ll get 2 eSIMs for the price of 1.';
    }
    $this->zone = $zone;

    $type = request()->get('type');
    $days = request()->get('days');
    $gb = request()->get('gb');

    $query = Plans::active()
        ->byZone($zone->id)
        ->nonReseller();

    if ($type === 'unlimited') {
        $query->where('is_unlimited', 1);

        if ($days) {
            $query->where('Days', $days);
        }

    } else {
        $query->where('is_unlimited', 0);

        if ($gb) {
            $query->where('GB', $gb);
        }
    }

    $this->plans = $query->get();

    $this->selectedPlanId = $this->plans->first()?->id;

    $countryIds = array_filter(
        array_map('trim', explode(',', $zone->countries))
    );

    $this->countries = Country::activeCountries($countryIds)->get();
}

    // public function mount(Zone $zone)
    // {
    //     $this->zone = $zone;
    //     // Plans
    //  $this->plans = Plans::active()
    // ->byZone($zone->id)
    // ->nonReseller()
    // ->get();

    //     //  Zone ke country IDs
    //     $countryIds = array_filter(
    //         array_map('trim', explode(',', $zone->countries))
    //     );

    //     //  Countries table flags + names
    //     $this->countries = Country::activeCountries($countryIds)->get();

    //     $this->selectedPlanId = $this->plans->first()?->id;

    // }

    public function continue($total)
    {
        try {
            $plan = Plans::findOrFail($this->selectedPlanId);

            CartService::add([
                'zone_id' => $this->zone->id,
                'zone_name' => $this->zone->name,
                'plan_id' => $plan->id,
                 'plan_name' => $plan->name,
    'is_unlimited' => (bool) $plan->is_unlimited,  // ✅ add this
    'days' => $plan->Days,                          // ✅ add this
    'gb' => $plan->GB,  
                'price' => $plan->USD,
                'quantity' => $this->quantity,
                'addons' => $this->addons,
                'total' => $total,
            ]);

            // ✅ success toast
            $this->dispatch(
                'toast',
                type: 'success',
                message: 'Plan added to cart successfully'
            );

            return redirect()->route('cart');

        } catch (\Throwable $e) {

            // ❌ error toast
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Something went wrong. Please try again.'
            );

            // log for debugging
            logger()->error('Cart add failed', [
                'error' => $e->getMessage()
            ]);
        }
    }


    public function render()
    {
        return view('livewire.plansDetails')->layout('layouts.app');
    }
}
