<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\CartService;
use App\Models\Order;

class RechargeOrder extends Component
{
    public ?Order $order = null;
    public int $orderId;

    public bool $showChangePlanModal = false;

    // ── Addons ─────────────────────────────────────────
    public bool  $addonTalkTime  = false;
    public bool  $addonAutoTopup = false;
    public float $talkTimePrice  = 10.00;

    public function mount(string $msisdn): void
    {
        $order = Order::where('msisdn', $msisdn)
            ->where('userid', Auth::id())
            ->orderByDesc('id')
            ->first();

        abort_if(!$order, 404);

        $this->order   = $order;
        $this->orderId = $order->id;
    }

    // ── Fetch Available Plans ─────────────────────────
    private function getPlans(): array
    {
        $plans = \App\Models\Plans::where('plans.status', 'A')
            ->join('zones', 'plans.zone_id', '=', 'zones.id')
            ->where('zones.status', 'A')
            ->orderBy('zones.zone_name')
            ->orderBy('plans.USD')
            ->get([
                'plans.id',
                'plans.GB',
                'plans.Days',
                'plans.USD',
                'plans.zone_id',
                'zones.zone_name'
            ]);

        $grouped = [];

        foreach ($plans as $p) {
            $grouped[$p->zone_name][] = [
                'id'        => $p->id,
                'label'     => $p->GB . 'GB / ' . $p->Days . ' Days',
                'price'     => (float) $p->USD,
                'zone_name' => $p->zone_name,
                'zone_id'   => $p->zone_id,
            ];
        }

        return $grouped;
    }

    // ── Renew Existing Plan ─────────────────────────
    public function renewPlan(): void
    {
        try {

            $plan = $this->order->plan;

            if (!$plan) {
                $this->dispatch('toast', type: 'error', message: 'Plan not found.');
                return;
            }

            $base  = (float) $plan->USD;
            $total = $base + ($this->addonTalkTime ? $this->talkTimePrice : 0);

            CartService::clear();

            CartService::add([
                'plan_id'           => $plan->id,
                'plan_name'         => $plan->GB . 'GB / ' . $plan->Days . ' Days',
                'zone_id'           => $plan->zone_id,
                'zone_name'         => $this->order->plan?->zone?->zone_name ?? '',
                'price'             => $base,
                'quantity'          => 1,
                'total'             => $total,
                'order_type'        => 'recharge',
                'recharge_iccid'    => $this->order->iccid?->ICCID ?? '',
                'recharge_order_id' => $this->order->id,
                'addons'            => [
                    'talk_time'  => [
                        'enabled' => $this->addonTalkTime,
                        'qty'     => 1,
                        'price'   => $this->addonTalkTime ? $this->talkTimePrice : 0
                    ],
                    'auto_topup' => [
                        'enabled' => $this->addonAutoTopup
                    ],
                ],
            ]);

            $this->redirect(route('checkout'));

        } catch (\Throwable $e) {

            Log::error('Renew Plan Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            $this->dispatch('toast', type: 'error', message: 'Something went wrong.');
        }
    }

    // ── Modal Controls ─────────────────────────
    public function openChangePlan(): void
    {
        $this->showChangePlanModal = true;
    }

    public function closeChangePlan(): void
    {
        $this->showChangePlanModal = false;
    }

    // ── Confirm Change Plan (FIXED) ─────────────────────────
    public function confirmChangePlan($planId, $price, $label, $zoneName = '')
    {
        try {

            Log::info('Confirm Change Plan Called', [
                'planId'   => $planId,
                'price'    => $price,
                'label'    => $label,
                'zoneName' => $zoneName,
                'user'     => auth()->id(),
            ]);

            if (!$planId) {
                throw new \Exception('Plan ID missing');
            }

          $plan = \App\Models\Plans::where('plans.id', $planId)
    ->where('plans.status', 'A')
    ->join('zones', 'plans.zone_id', '=', 'zones.id')
    ->where('zones.status', 'A')
    ->first([
        'plans.id',
        'plans.zone_id',
        'zones.zone_name'
    ]);

            if (!$plan) {
                $this->dispatch('toast', type: 'error', message: 'Invalid plan selected.');
                return;
            }

            $total = (float)$price + ($this->addonTalkTime ? $this->talkTimePrice : 0);

            CartService::clear();

            CartService::add([
                'plan_id'           => $planId,
                'plan_name'         => $label,
                'zone_id'           => $plan->zone_id,
                'zone_name'         => $plan->zone_name,
                'price'             => (float)$price,
                'quantity'          => 1,
                'total'             => $total,
                'order_type'        => 'recharge',
                'recharge_iccid'    => $this->order->iccid?->ICCID ?? '',
                'recharge_order_id' => $this->order->id,
                'addons'            => [
                    'talk_time'  => [
                        'enabled' => $this->addonTalkTime,
                        'qty'     => 1,
                        'price'   => $this->addonTalkTime ? $this->talkTimePrice : 0
                    ],
                    'auto_topup' => [
                        'enabled' => $this->addonAutoTopup
                    ],
                ],
            ]);

            Log::info('Cart Added Successfully');

            $this->redirect(route('checkout'));

        } catch (\Throwable $e) {

            Log::error('Confirm Change Plan Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            $this->dispatch('toast', type: 'error', message: 'Something went wrong.');
        }
    }

    // ── Render ─────────────────────────
    public function render()
    {
        return view('livewire.user.recharge-order', [
            'availablePlans' => $this->showChangePlanModal
                ? $this->getPlans()
                : [],
        ])->layout('layouts.app');
    }
}