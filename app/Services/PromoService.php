<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PromoService — with full logging on every step
 *
 * Tables:
 *   1. promocodes_main — promo definition
 *   2. promocodes      — usage tracking
 *
 * Log channel: default (laravel.log)
 * Har step pe log entry hoti hai — success aur failure dono pe.
 */
class PromoService
{
    // ─────────────────────────────────────────────────────────────────────
    //  ENTRY POINT
    // ─────────────────────────────────────────────────────────────────────

    public function validate(string $code, int $userId, array $cart): array
    {
        Log::info('[Promo] validate() started', [
            'code'    => $code,
            'user_id' => $userId,
            'cart_items' => count($cart),
        ]);

        // ── Step 1: Code exist + active ───────────────────────────────────
        $promo = DB::table('promocodes_main')
            ->where('promocode_name', $code)
            ->where('status_id', 'A')
            ->first();

        if (!$promo) {
            Log::warning('[Promo] Step 1 FAILED — code not found or inactive', [
                'code'    => $code,
                'user_id' => $userId,
            ]);
            return $this->fail('Invalid promo code.');
        }

        Log::info('[Promo] Step 1 PASSED — code found', [
            'promo_id'   => $promo->id,
            'promo_type' => $promo->promo_type,
            'code'       => $code,
        ]);

        // ── Step 2: Date window ───────────────────────────────────────────
        $today = now()->toDateString();

        if ($promo->activation_date && $today < $promo->activation_date) {
            Log::warning('[Promo] Step 2 FAILED — not yet active', [
                'code'            => $code,
                'today'           => $today,
                'activation_date' => $promo->activation_date,
            ]);
            return $this->fail('This promo code is not yet active.');
        }

        if ($promo->expiry_date && $today > $promo->expiry_date) {
            Log::warning('[Promo] Step 2 FAILED — expired', [
                'code'        => $code,
                'today'       => $today,
                'expiry_date' => $promo->expiry_date,
            ]);
            return $this->fail('This promo code has expired.');
        }

        Log::info('[Promo] Step 2 PASSED — date window valid', [
            'code'            => $code,
            'activation_date' => $promo->activation_date ?? 'none',
            'expiry_date'     => $promo->expiry_date     ?? 'none',
        ]);

        // ── Step 3: Global redeem_limit ───────────────────────────────────
        if ($promo->redeem_limit !== null) {
            $totalUsed = DB::table('promocodes')
                ->where('reference_id', $promo->id)
                ->sum('redeem_counter');

            Log::info('[Promo] Step 3 — global usage check', [
                'code'         => $code,
                'promo_id'     => $promo->id,
                'total_used'   => $totalUsed,
                'redeem_limit' => $promo->redeem_limit,
            ]);

            if ($totalUsed >= $promo->redeem_limit) {
                Log::warning('[Promo] Step 3 FAILED — global limit reached', [
                    'code'         => $code,
                    'total_used'   => $totalUsed,
                    'redeem_limit' => $promo->redeem_limit,
                ]);
                return $this->fail('This promo code has reached its usage limit.');
            }
        } else {
            Log::info('[Promo] Step 3 SKIPPED — redeem_limit is NULL (unlimited)', [
                'code' => $code,
            ]);
        }

        Log::info('[Promo] Step 3 PASSED — within global limit');

        // ── Step 4: Per-user once-check ───────────────────────────────────
        if (in_array($promo->promo_type, ['buy1get1', 'freeEsim'])) {
            $alreadyUsed = DB::table('promocodes')
                ->where('reference_id', $promo->id)
                ->where('user_id', $userId)
                ->where('used', 1)
                ->exists();

            Log::info('[Promo] Step 4 — per-user once-check', [
                'code'         => $code,
                'promo_type'   => $promo->promo_type,
                'user_id'      => $userId,
                'already_used' => $alreadyUsed,
            ]);

            if ($alreadyUsed) {
                Log::warning('[Promo] Step 4 FAILED — user already used this code', [
                    'code'    => $code,
                    'user_id' => $userId,
                ]);
                return $this->fail('You have already used this promo code.');
            }
        } else {
            Log::info('[Promo] Step 4 SKIPPED — type allows multiple use', [
                'code'       => $code,
                'promo_type' => $promo->promo_type,
            ]);
        }

        Log::info('[Promo] Step 4 PASSED — user has not used this code');

        // ── Step 5: Type-specific validation ─────────────────────────────
        Log::info('[Promo] Step 5 — type-specific validation', [
            'code'       => $code,
            'promo_type' => $promo->promo_type,
        ]);

        $result = match ($promo->promo_type) {
            'discount'  => $this->validateDiscount($promo, $cart),
            'bonusData' => $this->validateBonusData($promo, $cart),
            'freeEsim'  => $this->validateFreeEsim($promo, $cart),
            'buy1get1'  => $this->validateBuy1Get1($promo, $cart),
            default     => $this->fail('Unknown promo type.'),
        };

        if ($result['valid']) {
            Log::info('[Promo] validate() COMPLETE — all steps passed', [
                'code'       => $code,
                'user_id'    => $userId,
                'promo_type' => $promo->promo_type,
                'savings'    => $result['savings'] ?? 0,
            ]);
        } else {
            Log::warning('[Promo] Step 5 FAILED — type-specific check', [
                'code'    => $code,
                'message' => $result['message'],
            ]);
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  TYPE VALIDATORS
    // ─────────────────────────────────────────────────────────────────────

    private function validateDiscount(object $promo, array $cart): array
    {
        $baseTotal = collect($cart)
            ->filter(fn($i) => empty($i['is_promo_free']))
            ->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1));

        Log::info('[Promo] validateDiscount()', [
            'promo_id'       => $promo->id,
            'base_total'     => $baseTotal,
            'discount_pct'   => $promo->promo_discount,
        ]);

        if ($baseTotal <= 0) {
            return $this->fail('Your cart is empty.');
        }

        $savings = round($baseTotal * ($promo->promo_discount / 100), 2);

        Log::info('[Promo] discount savings calculated', [
            'base_total'   => $baseTotal,
            'discount_pct' => $promo->promo_discount,
            'savings'      => $savings,
        ]);

        return $this->success(
            message: "{$promo->promo_discount}% discount applied — you save \${$savings}!",
            promo: [
                'id'       => $promo->id,
                'code'     => $promo->promocode_name,
                'type'     => 'discount',
                'discount' => (int) $promo->promo_discount,
                'savings'  => $savings,
            ],
            savings: $savings
        );
    }

    private function validateBonusData(object $promo, array $cart): array
    {
$planIds = collect($cart)
        ->pluck('plan_id')
        ->filter()
        ->unique()
        ->values()
        ->toArray();

    $hasDataPlan = !empty($planIds) && DB::table('plans')
        ->whereIn('id', $planIds)
        ->where('GB', '>', 0)
        ->exists();
        Log::info('[Promo] validateBonusData()', [
            'promo_id'      => $promo->id,
            'promo_amount'  => $promo->promo_amount,
            'has_data_plan' => $hasDataPlan,
        ]);

        if (!$hasDataPlan) {
            return $this->fail('This promo requires a data plan in your cart.');
        }

        // promo_amount = GB value (e.g. 2 = 2GB, NOT 2MB)
        $label = $promo->promo_amount . 'GB';

        return $this->success(
            message: "Bonus {$label} data will be added on activation!",
            promo: [
                'id'          => $promo->id,
                'code'        => $promo->promocode_name,
                'type'        => 'bonusData',
                'amount'      => (int) $promo->promo_amount,
                'bonus_label' => $label,   // blade mein directly use karo
            ]
        );
    }

    private function validateFreeEsim(object $promo, array $cart): array
    {
        Log::info('[Promo] validateFreeEsim() — looking for free plan', [
            'promo_id'     => $promo->id,
            'required_gb'  => $promo->promo_amount,
        ]);

        $freePlan = DB::table('plans')
            ->where('GB', $promo->promo_amount)
            ->where('USD', 0)
            ->where('status_id', 'A')
            ->first();

        if (!$freePlan) {
            Log::error('[Promo] validateFreeEsim() FAILED — no free plan found', [
                'required_gb' => $promo->promo_amount,
            ]);
            return $this->fail('Free eSIM plan is currently unavailable. Please contact support.');
        }

        Log::info('[Promo] validateFreeEsim() — free plan found', [
            'free_plan_id' => $freePlan->id,
            'gb'           => $freePlan->GB,
        ]);

        return $this->success(
            message: "Free {$promo->promo_amount}GB eSIM added to your order!",
            promo: [
                'id'           => $promo->id,
                'code'         => $promo->promocode_name,
                'type'         => 'freeEsim',
                'free_plan_id' => $freePlan->id,
                'gb'           => (int) $promo->promo_amount,
            ]
        );
    }

    private function validateBuy1Get1(object $promo, array $cart): array
    {
        $groups = collect($cart)
            ->filter(fn($i) => empty($i['is_promo_free']))
            ->groupBy('zone_id');

        Log::info('[Promo] validateBuy1Get1() — checking zones', [
            'promo_id'   => $promo->id,
            'zone_count' => $groups->count(),
        ]);

        foreach ($groups as $zoneId => $items) {
            $distinctPlans = $items->pluck('plan_id')->unique()->count();

            Log::info('[Promo] B1G1 zone check', [
                'zone_id'        => $zoneId,
                'distinct_plans' => $distinctPlans,
            ]);

            if ($distinctPlans > 1) {
                Log::warning('[Promo] B1G1 FAILED — multiple plans in zone', [
                    'zone_id'        => $zoneId,
                    'distinct_plans' => $distinctPlans,
                ]);
                return $this->fail('Buy 1 Get 1 applies to one plan per zone only.');
            }
        }

        return $this->success(
            message: 'Buy 1 Get 1 Free applied — double eSIMs, same price!',
            promo: [
                'id'   => $promo->id,
                'code' => $promo->promocode_name,
                'type' => 'buy1get1',
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    //  STATE MUTATIONS
    // ─────────────────────────────────────────────────────────────────────

    public function applyToState(object $component, array $promo): void
    {
        Log::info('[Promo] applyToState() called', [
            'promo_type' => $promo['type'],
            'code'       => $promo['code'],
        ]);

        match ($promo['type']) {
            'discount'  => $this->applyDiscount($component, $promo),
            'bonusData' => $this->applyBonusData($component, $promo),
            'freeEsim'  => $this->applyFreeEsim($component, $promo),
            'buy1get1'  => $this->applyBuy1Get1($component, $promo),
            default     => null,
        };

        Log::info('[Promo] applyToState() done', [
            'promo_type' => $promo['type'],
            'new_grand_total' => $component->grandTotal,
            'cart_items'      => count($component->cart),
        ]);
    }

    private function applyDiscount(object $component, array $promo): void
    {
        $baseTotal = collect($component->cart)
            ->filter(fn($i) => empty($i['is_promo_free']))
            ->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1));

        $addonTotal = collect($component->cart)->sum(function ($i) {
            if (empty($i['addons']['talk_time']['enabled'])) return 0;
            return ($i['addons']['talk_time']['price'] ?? 0)
                 * ($i['addons']['talk_time']['qty']   ?? 1);
        });

        $discountedBase        = round($baseTotal * (1 - $promo['discount'] / 100), 2);
        $component->grandTotal = $discountedBase + $addonTotal;

        Log::info('[Promo] applyDiscount() — grandTotal updated', [
            'base_total'     => $baseTotal,
            'addon_total'    => $addonTotal,
            'discounted_base'=> $discountedBase,
            'new_grand_total'=> $component->grandTotal,
            'discount_pct'   => $promo['discount'],
        ]);
    }

    /**
     * bonusData — sirf PEHLE real item pe is_bonus_item=true flag lagao.
     * Blade is flag se sirf pehle plan ke neeche bonus line dikhata hai.
     * grandTotal unchanged.
     */
private function applyBonusData(object $component, array $promo): void
{
    if (empty($component->cart)) {
        return;
    }

    // Clear old flags
    foreach ($component->cart as &$item) {
        unset($item['is_bonus_item']);
    }
    unset($item);

    // Apply to FIRST item
    $component->cart = array_values($component->cart); // ensure index 0 exists
    $component->cart[0]['is_bonus_item'] = true;

    // 🔥 IMPORTANT — regroup cart for Blade
    $component->groupedCart = collect($component->cart)
        ->groupBy('zone_id')
        ->toArray();

    Log::info('[Promo] Bonus applied to first cart item', [
        'plan_id' => $component->cart[0]['plan_id'] ?? null,
        'bonus'   => $promo['amount'] ?? null
    ]);
}

    private function applyFreeEsim(object $component, array $promo): void
    {
        $firstReal = collect($component->cart)
            ->filter(fn($i) => empty($i['is_promo_free']))
            ->first();

        $component->cart[] = [
            'plan_id'       => $promo['free_plan_id'],
            'plan_name'     => "{$promo['gb']}GB Free eSIM (Promo)",
            'price'         => 0.00,
            'quantity'      => 1,
            'total'         => 0.00,
            'addons'        => [],
            'is_promo_free' => true,
            'order_type'    => 'newsim',
            'zone_id'       => $firstReal['zone_id']  ?? null,
            'zone_name'     => $firstReal['zone_name'] ?? 'Promo',
            'GB'            => $promo['gb'],
        ];

        Log::info('[Promo] applyFreeEsim() — synthetic item injected', [
            'free_plan_id' => $promo['free_plan_id'],
            'gb'           => $promo['gb'],
            'cart_count'   => count($component->cart),
        ]);
    }

 private function applyBuy1Get1(object $component, array $promo): void
{
    // 1️⃣ Remove existing free items first
    $component->cart = array_values(
        array_filter($component->cart, fn($i) => empty($i['is_promo_free']))
    );

    // 2️⃣ Clear old b1g1 flags
    foreach ($component->cart as &$item) {
        unset($item['b1g1_qty']);
    }
    unset($item);

    if (empty($component->cart)) {
        return;
    }

    // 3️⃣ Check distinct plans in whole cart
    $distinctPlans = collect($component->cart)
        ->pluck('plan_id')
        ->unique()
        ->count();

    if ($distinctPlans === 1) {

        // ───── SAME PLAN CASE ─────
        $firstItem = &$component->cart[0];
        $qty       = (int) ($firstItem['quantity'] ?? 1);

        if ($qty === 1) {
            // Inject 1 FREE copy
            $component->cart[] = [
                'plan_id'       => $firstItem['plan_id'],
                'plan_name'     => $firstItem['plan_name'],
                'price'         => 0.00,
                'quantity'      => 1,
                'total'         => 0.00,
                'addons'        => [],
                'is_promo_free' => true,
                'order_type'    => $firstItem['order_type'] ?? 'newsim',
                'zone_id'       => $firstItem['zone_id'],
                'zone_name'     => $firstItem['zone_name'] ?? '',
            ];
        } else {
            // Reduce 1 quantity as free
            $firstItem['b1g1_qty'] = $qty - 1;
            $firstItem['total']    = round(($firstItem['price'] ?? 0) * ($qty - 1), 2);
        }

    } else {

        // ───── DIFFERENT PLANS CASE ─────
        $cheapest = collect($component->cart)
            ->sortBy('price')
            ->first();

        $component->cart[] = [
            'plan_id'       => $cheapest['plan_id'],
            'plan_name'     => $cheapest['plan_name'],
            'price'         => 0.00,
            'quantity'      => 1,
            'total'         => 0.00,
            'addons'        => [],
            'is_promo_free' => true,
            'order_type'    => $cheapest['order_type'] ?? 'newsim',
            'zone_id'       => $cheapest['zone_id'],
            'zone_name'     => $cheapest['zone_name'] ?? '',
        ];
    }

    // 4️⃣ Recalculate total
    $component->grandTotal = round(
        collect($component->cart)->sum('total'),
        2
    );

    // 5️⃣ Regroup for Blade
    $component->groupedCart = collect($component->cart)
        ->groupBy('zone_id')
        ->toArray();

    Log::info('[Promo] applyBuy1Get1() CART LEVEL done', [
        'grand_total' => $component->grandTotal,
        'cart_items'  => count($component->cart),
    ]);
}

    // ─────────────────────────────────────────────────────────────────────
    //  DB WRITE
    // ─────────────────────────────────────────────────────────────────────

    public function recordRedemption(array $promo, int $userId, int $orderId, string $masterUid): void
    {
        Log::info('[Promo] recordRedemption() — writing to DB', [
            'promo_id'   => $promo['id'],
            'promo_code' => $promo['code'],
            'promo_type' => $promo['type'],
            'user_id'    => $userId,
            'order_id'   => $orderId,
            'master_uid' => $masterUid,
        ]);

        try {
            DB::table('promocodes')->insert([
                'reference_id'   => $promo['id'],
                'user_id'        => $userId,
                'promocode'      => $promo['code'],
                'order_id'       => $orderId,
                'master_uid'     => $masterUid,
                'redeemed_at'    => now(),
                'used'           => 1,
                'redeem_counter' => 1,
                'type'           => 1,
            ]);

            Log::info('[Promo] recordRedemption() SUCCESS', [
                'promo_id'   => $promo['id'],
                'promo_code' => $promo['code'],
                'user_id'    => $userId,
                'order_id'   => $orderId,
            ]);

        } catch (\Exception $e) {
            Log::error('[Promo] recordRedemption() FAILED', [
                'error'      => $e->getMessage(),
                'promo_id'   => $promo['id'],
                'promo_code' => $promo['code'],
                'user_id'    => $userId,
                'order_id'   => $orderId,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function fail(string $message): array
    {
        return ['valid' => false, 'message' => $message];
    }

    private function success(string $message, array $promo, float $savings = 0): array
    {
        return ['valid' => true, 'message' => $message, 'promo' => $promo, 'savings' => $savings];
    }
}