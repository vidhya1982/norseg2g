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

    public $addons = [
        'talk_time' => [
            'enabled' => true,
            'qty' => 1,
            'price' => 10
        ],
        'auto_topup' => [
            'enabled' => false
        ]
    ];

    public function mount(Zone $zone)
    {
        $this->zone = $zone;
        // Plans
        $this->plans = Plans::active()->byZone($zone->id)->get();

        //  Zone ke country IDs
        $countryIds = array_filter(
            array_map('trim', explode(',', $zone->countries))
        );

        //  Countries table flags + names
        $this->countries = Country::activeCountries($countryIds)->get();

        $this->selectedPlanId = $this->plans->first()?->id;

    }

    public function continue($total)
    {
        try {
            $plan = Plans::findOrFail($this->selectedPlanId);

            CartService::add([
                'zone_id' => $this->zone->id,
                'zone_name' => $this->zone->name,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
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
