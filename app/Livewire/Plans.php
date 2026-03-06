<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Plans as PlanModel;
use App\Models\Zone;
use App\Services\CartService;

class Plans extends Component
{
    public $zone;
    public $unlimitedPlans;
    public $budgetPlans;
  public bool $hasBonusPromo = false;
public bool $hasBOGOPromo  = false;
public string $promoBannerText = '';

    public array $dayImages = [
        5  => 'europe.png',
        10 => 'uk.png',
        21 => 'world.png',
    ];

    public array $imageMap = [
        3  => 'world.png',
        5  => 'europe.png',
        10 => 'uk.png',
        20 => 'world2.jpeg',
    ];

    public function mount()
    {
         $promoFromUrl = request()->query('promo');
    if ($promoFromUrl) {
        $promoFromUrl = strtoupper(trim($promoFromUrl));

        // Purana promo alag hai toh cart clear karo
        $existingPromo = session('pending_promo');
        if ($existingPromo && $existingPromo !== $promoFromUrl) {
            CartService::clear();
        }

        session(['pending_promo' => $promoFromUrl]);
    }

    $this->hasBonusPromo = session('pending_promo') === 'NORSETEST';
    $this->hasBOGOPromo  = session('pending_promo') === 'NORSEBOGO';

    if ($this->hasBonusPromo) {
        $this->promoBannerText = 'Select any Budget Plan below to get +2GB bonus data free on activation.';
    }

    if ($this->hasBOGOPromo) {
        $this->promoBannerText = 'Select any plan below — Buy 1 Get 1 Free! Pay for one, get two eSIMs.';
    }

        $this->zone = Zone::find(1);

        $this->unlimitedPlans = PlanModel::where('zone_id', 1)
            ->where('is_unlimited', 1)
            ->whereIn('Days', [5, 10, 21])
            ->selectRaw('MIN(id) as id, Days, MIN(USD) as USD')
            ->groupBy('Days')
            ->orderByRaw("FIELD(Days,5,10,21)")
            ->get();

        $this->budgetPlans = PlanModel::where('zone_id', 1)
    ->where('is_unlimited', 0)
    ->where('USD', '>', 0)   // ✅ free esim hata diya
    ->whereIn('GB', [3, 5, 10, 20])
    ->selectRaw('MIN(id) as id, GB, MIN(Days) as Days, MIN(USD) as USD')
    ->groupBy('GB')
    ->orderByRaw("FIELD(GB,3,5,10,20)")
    ->get();
    }

    public function render()
    {
        return view('livewire.plans')->layout('layouts.app');
    }
}