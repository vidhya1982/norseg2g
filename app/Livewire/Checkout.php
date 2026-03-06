<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CartService;
use App\Services\AirwallexService;
use App\Services\PromoService;
use App\Jobs\ProcessEsimActivation;
use App\Jobs\ProcessEsimRecharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrdersInitiated;
use App\Models\PaymentProfileLog;

class Checkout extends Component
{
    public array $cart = [];
    public float $grandTotal = 0;
    public array $groupedCart = [];
    public bool $isGuest = true;
    public ?string $clientSecret = null;
    public ?string $intentId = null;
    public string $paymentMethod = 'card';
    public ?int $currentOrderId = null;

    public bool $saveCard = false;
    public ?string $selectedConsentId = null;
    public bool $usingSavedCard = false;
    public array $savedCards = [];
    public bool $isAutoPromo = false;

    public string $promoCode = '';
    public array $appliedPromo = [];
    public ?string $promoError = null;
    public float $discountAmount = 0;

    protected $listeners = ['cart-sync' => 'refreshTotals'];

    public function mount(): void
    {
        Log::info('[DEBUG mount]', [
            'session_all' => session()->all(),
            'cart'        => CartService::get(),
        ]);

        $this->isGuest = !Auth::check();
        $this->loadCart();

        if (!$this->isGuest) {
            $this->loadSavedCards();
        }

        $promoFromUrl = request()->query('promo');

        if ($promoFromUrl) {
            $this->isAutoPromo = true;
            $promoFromUrl      = strtoupper(trim($promoFromUrl));

            $promoDef = DB::table('promocodes_main')
                ->where('promocode_name', $promoFromUrl)
                ->where('status_id', 'A')
                ->first();

            // freeEsim: cart clear karo — applyPromo() applyFreeEsim() ke zariye inject karega
            // mount() mein khud inject NAHI karna — warna duplicate ho jayega
            if ($promoDef && $promoDef->promo_type === 'freeEsim') {
                CartService::clear();
                $this->loadCart();
            }

            if ($this->isGuest) {
                session(['pending_promo' => $promoFromUrl]);
            } else {
                $this->promoCode = $promoFromUrl;
                $this->applyPromo(app(PromoService::class));
            }
        }

        if (!$this->isGuest) {
            $pendingPromo = session()->pull('pending_promo');

            if ($pendingPromo && empty($this->appliedPromo)) {
                // Stale free items cart mein hain toh pehle clear karo
                if (collect($this->cart)->contains('is_promo_free', true)) {
                    CartService::clear();
                    $this->loadCart();
                }

                $this->promoCode = $pendingPromo;
                $this->applyPromo(app(PromoService::class));
            }
        }

        // Safety net: appliedPromo set nahi hua but free item cart mein hai
        if (
            empty($this->appliedPromo)
            && collect($this->cart)->contains('is_promo_free', true)
            && !empty($this->promoCode)
        ) {
            Log::warning('[Checkout] appliedPromo empty but free item in cart — forcing freeEsim state', [
                'promoCode' => $this->promoCode,
            ]);

            $promoDef = DB::table('promocodes_main')
                ->where('promocode_name', $this->promoCode)
                ->where('status_id', 'A')
                ->first();

            if ($promoDef && $promoDef->promo_type === 'freeEsim') {
                $this->appliedPromo = [
                    'type' => 'freeEsim',
                    'code' => $this->promoCode,
                    'gb'   => (int) ($promoDef->promo_amount ?? 1),
                ];
            }
        }
    }

    public function refreshTotals(): void
    {
        $this->loadCart();
    }

    private function loadCart(): void
    {
        $this->cart        = CartService::get();
        $this->grandTotal  = round(collect($this->cart)->sum('total'), 2);
        $this->groupedCart = collect($this->cart)
            ->groupBy(fn($item) => isset($item['is_unlimited']) && $item['is_unlimited'] ? 'unlimited' : 'budget')
            ->values()
            ->map(fn($group) => $group->values()->toArray())
            ->toArray();
    }

    private function loadSavedCards(): void
    {
        $rows = PaymentProfileLog::savedCards(Auth::id())->get();

        $this->savedCards = $rows->map(fn($c) => [
            'consent_id' => $c->consent_id,
            'label'      => $c->display_label,
            'is_default' => $c->is_default,
            'brand'      => $c->brand,
            'last4'      => $c->last4,
        ])->toArray();

        $default = collect($this->savedCards)->firstWhere('is_default', true);
        if ($default) {
            $this->selectedConsentId = $default['consent_id'];
            $this->usingSavedCard    = true;
        }
    }

    private function isRechargeOrder(): bool
    {
        return collect($this->cart)->contains('order_type', 'recharge');
    }

    private function isFreeEsimOnlyCart(): bool
    {
        return
            ($this->appliedPromo['type'] ?? '') === 'freeEsim'
            && $this->grandTotal == 0
            && !empty($this->cart)
            && collect($this->cart)->every(fn($i) => !empty($i['is_promo_free']));
    }

    public function selectSavedCard(string $consentId): void
    {
        $this->selectedConsentId = $consentId;
        $this->usingSavedCard    = true;
    }

    public function useNewCard(): void
    {
        $this->selectedConsentId = null;
        $this->usingSavedCard    = false;
    }

    public function applyPromo(PromoService $promoService): void
    {
        $this->promoError = null;

        if (empty(trim($this->promoCode))) {
            $this->promoError = 'Please enter a promo code.';
            return;
        }

        // ── KEY FIX: pehle se free item hai toh clear karo ────────────────
        // applyFreeEsim() ek aur inject karega → bina is check ke 2 items ban jaate hain
        if (collect($this->cart)->contains('is_promo_free', true)) {
            Log::info('[Checkout] applyPromo — clearing stale free items before re-apply');
            $paidItemsOnly = array_values(
                array_filter($this->cart, fn($i) => empty($i['is_promo_free']))
            );
            CartService::clear();
            foreach ($paidItemsOnly as $item) {
                CartService::add($item);
            }
            $this->loadCart();
        }

        $result = $promoService->validate(
            code:   strtoupper(trim($this->promoCode)),
            userId: Auth::id(),
            cart:   $this->cart,
        );

        if (!$result['valid']) {
            $this->promoError = $result['message'];
            return;
        }

        $this->appliedPromo   = $result['promo'];
        $this->discountAmount = $result['savings'] ?? 0;

        // applyToState() → applyFreeEsim() exactly 1 item inject karega
        $promoService->applyToState($this, $result['promo']);

        Log::info('[Checkout] applyPromo complete', [
            'promo_type' => $this->appliedPromo['type'] ?? 'n/a',
            'cart_count' => count($this->cart),
            'grandTotal' => $this->grandTotal,
        ]);
    }

    public function removePromo(): void
    {
        if (($this->appliedPromo['type'] ?? '') === 'freeEsim') {
            $this->cart = array_values(
                array_filter($this->cart, fn($i) => empty($i['is_promo_free']))
            );
        }

        if (($this->appliedPromo['type'] ?? '') === 'buy1get1') {
            $this->cart = array_values(
                array_filter($this->cart, fn($i) => empty($i['is_promo_free']))
            );
            foreach ($this->cart as &$item) {
                if (isset($item['b1g1_qty'])) {
                    $item['total'] = round(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2);
                    unset($item['b1g1_qty']);
                }
            }
            unset($item);
        }

        foreach ($this->cart as &$item) {
            unset($item['is_bonus_item']);
        }
        unset($item);

        $this->appliedPromo   = [];
        $this->promoCode      = '';
        $this->promoError     = null;
        $this->discountAmount = 0;

        $this->loadCart();
    }

    public function payNow(AirwallexService $service): void
    {
        if ($this->isGuest) {
            $this->dispatch('toast', type: 'error', message: 'Please login to continue.');
            return;
        }

        if (empty($this->cart)) {
            $this->dispatch('toast', type: 'error', message: 'Your cart is empty.');
            return;
        }

        // ── FREE eSIM fast-path — Airwallex completely bypass ─────────────
        if ($this->isFreeEsimOnlyCart()) {

            Log::info('[Checkout] freeEsim fast-path triggered', [
                'promo_code' => $this->appliedPromo['code'] ?? 'n/a',
                'cart_count' => count($this->cart),
                'grandTotal' => $this->grandTotal,
            ]);

            // Last-resort dedup — should not be needed after applyPromo fix
            $freeItems = collect($this->cart)->filter(fn($i) => !empty($i['is_promo_free']));
            if ($freeItems->count() > 1) {
                Log::warning('[Checkout] freeEsim — duplicate free items, deduplicating', [
                    'count' => $freeItems->count(),
                ]);
                $nonFree    = collect($this->cart)->filter(fn($i) => empty($i['is_promo_free']))->values()->toArray();
                $this->cart = array_merge($nonFree, [$freeItems->first()]);
            }

            DB::beginTransaction();
            try {
                $user        = Auth::user();
                $fakeTransId = 'FREE-PROMO-' . strtoupper($this->appliedPromo['code'] ?? 'X') . '-' . time();

                $order = OrdersInitiated::create([
                    'userid'        => $user->id,
                    'usd'           => 0,
                    'paymentStatus' => 'Paid',
                    'custom'        => 'checkout',
                    'transId'       => $fakeTransId,
                ]);

                $this->currentOrderId = $order->id;
                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('[Checkout] freeEsim — OrdersInitiated create failed', [
                    'error' => $e->getMessage(),
                ]);
                $this->dispatch('toast', type: 'error', message: 'Could not process free eSIM. Please contact support.');
                return;
            }

            $this->handleFreePromoSuccess($this->currentOrderId);
            return;
        }
        // ── END FREE eSIM fast-path ───────────────────────────────────────

        if ($this->usingSavedCard && $this->selectedConsentId) {
            $this->payWithSavedCard($service);
            return;
        }

        $allowedMethods = ['card', 'paypal', 'googlepay', 'applepay'];
        if (!in_array($this->paymentMethod, $allowedMethods)) {
            $this->paymentMethod = 'card';
        }

        $amount = max(0, $this->grandTotal);

        if ($amount <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Your cart total is zero. Please check your cart.');
            return;
        }

        DB::beginTransaction();
        try {
            $user       = Auth::user();
            $customerId = $service->ensureCustomer($user);

            $order = OrdersInitiated::create([
                'userid'        => $user->id,
                'usd'           => $amount,
                'paymentStatus' => 'Pending',
                'custom'        => $this->isRechargeOrder() ? 'recharge' : 'checkout',
            ]);

            $intent = $service->createPaymentIntent(
                amount:     $amount,
                orderId:    (string) $order->id,
                customerId: $customerId,
            );

            $consentClientSecret = null;
            if ($this->saveCard && $this->paymentMethod === 'card') {
                $consent             = $service->createPaymentConsent($customerId, 'customer');
                $consentClientSecret = $consent['client_secret'];

                DB::table('payment_profile_log')->insert([
                    'userId'           => $user->id,
                    'profileId'        => '0',
                    'paymentProfileId' => '0',
                    'orderId'          => $order->id,
                    'order_total'      => $amount,
                    'creationdate'     => now(),
                    'consent_id'       => $consent['id'],
                    'consent_status'   => 'PENDING',
                    'is_default'       => 0,
                ]);
            }

            $order->update(['transId' => $intent['id']]);
            $this->currentOrderId = $order->id;
            DB::commit();

            $this->intentId     = $intent['id'];
            $this->clientSecret = $intent['client_secret'];

            $this->dispatch(
                'startPayment',
                intentId:            $this->intentId,
                clientSecret:        $this->clientSecret,
                consentClientSecret: $consentClientSecret,
                method:              $this->paymentMethod,
                saveCard:            $this->saveCard,
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $service->clearTokenCache();
            Log::error('[Checkout] payNow failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Payment initialization failed. Please try again.');
        }
    }

    public function payWithSavedCard(AirwallexService $service): void
    {
        $amount = max(0, $this->grandTotal);

        $savedCard = PaymentProfileLog::where('userId', Auth::id())
            ->where('consent_id', $this->selectedConsentId)
            ->where('consent_status', 'VERIFIED')
            ->first();

        if (!$savedCard) {
            $this->dispatch('toast', type: 'error', message: 'Invalid saved card. Please choose another.');
            return;
        }

        DB::beginTransaction();
        try {
            $user  = Auth::user();
            $order = OrdersInitiated::create([
                'userid'        => $user->id,
                'usd'           => $amount,
                'paymentStatus' => 'Pending',
                'custom'        => $this->isRechargeOrder() ? 'recharge' : 'checkout',
            ]);

            $intent = $service->createPaymentIntent(
                amount:     $amount,
                orderId:    (string) $order->id,
                customerId: $user->airwallex_customer_id,
            );

            $order->update(['transId' => $intent['id']]);
            $this->currentOrderId = $order->id;

            $confirmed       = $service->confirmIntentWithConsent($intent['id'], $this->selectedConsentId);
            $confirmedStatus = $confirmed['status'] ?? '';
            DB::commit();

            if (in_array($confirmedStatus, ['SUCCEEDED', 'REQUIRES_CAPTURE'])) {
                $this->handlePaymentSuccess($intent['id'], $service);
            } else {
                $this->intentId     = $intent['id'];
                $this->clientSecret = $confirmed['client_secret'] ?? $intent['client_secret'];
                $this->dispatch(
                    'startPayment',
                    intentId:            $this->intentId,
                    clientSecret:        $this->clientSecret,
                    consentClientSecret: null,
                    method:              'card',
                    saveCard:            false,
                );
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Checkout] payWithSavedCard failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Payment failed. Please try a different card.');
        }
    }

    private function handleFreePromoSuccess(int $orderId): void
    {
        DB::beginTransaction();
        try {
            $order = OrdersInitiated::findOrFail($orderId);
            $user  = Auth::user();

            if (
                $order->paymentStatus === 'Paid'
                && !str_starts_with((string) ($order->transId ?? ''), 'FREE-PROMO-')
            ) {
                DB::commit();
                $this->redirect(route('user.orders'));
                return;
            }

            $fakeTransId      = $order->transId;
            $insertedOrderIds = [];

            // Dedup: plan_id per sirf 1 row — duplicate inject hone se protect karta hai
            $cartToProcess = collect($this->cart)
                ->unique('plan_id')
                ->values()
                ->toArray();

            Log::info('[Checkout] handleFreePromoSuccess — cart snapshot', [
                'original_count' => count($this->cart),
                'deduped_count'  => count($cartToProcess),
                'promo'          => $this->appliedPromo,
                'grandTotal'     => $this->grandTotal,
            ]);

            foreach ($cartToProcess as $item) {
                $qty = (int) ($item['quantity'] ?? 1);

                $plan        = DB::table('plans')->where('id', $item['plan_id'])->first();
                $GB          = $plan?->GB      ?? ($item['GB'] ?? $item['gb'] ?? 0);
                $SMS         = $plan?->SMS     ?? 0;
                $Days        = $plan?->Days    ?? 0;
                $planMoniker = $plan?->Moniker ?? '';

                Log::info('[Checkout] Free eSIM — inserting order row', [
                    'plan_id' => $item['plan_id'],
                    'GB'      => $GB,
                    'qty'     => $qty,
                ]);

                for ($i = 0; $i < $qty; $i++) {
                    $newId = DB::table('orders')->insertGetId([
                        'userid'           => $user->id,
                        'email'            => $user->email,
                        'plan_id'          => $item['plan_id'],
                        'plan_moniker'     => $planMoniker,
                        'customer_group'   => 'customer',
                        'GB'               => $GB,
                        'add_GB'           => '',
                        'Mins'             => 0,
                        'SMS'              => $SMS,
                        'Days'             => $Days,
                        'esimLive'         => '0',
                        'autorenew'        => '0',
                        'orderType'        => 'newsim',
                        'USD'              => 0.00,
                        'status'           => 'IN PROGRESS',
                        'paymentStatus'    => 'Paid',
                        'transId'          => $fakeTransId,
                        'transCode'        => '',
                        'authCode'         => '',
                        'msgCode'          => '',
                        'desc'             => 'free_promo',
                        'date'             => now(),
                        'plan_start_date'  => '0000-00-00 00:00:00',
                        'plan_end_date'    => '0000-00-00 00:00:00',
                        'reclaimDate'      => null,
                        'loc_update_at'    => now(),
                        'activationFrom'   => 'WEBSITE',
                        'activationBy'     => $user->id,
                        'activationName'   => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')),
                        'profileId'        => '',
                        'paymentProfileId' => '',
                        'inventoryId'      => null,
                        'msisdn'           => '',
                        'customerid'       => '',
                        'subscriberId'     => '',
                        'my_uid'           => $orderId,
                        'apiRequest'       => '',
                        'apiResponse'      => '',
                        'apiDetails'       => '',
                        'reclaimApi'       => '',
                        'stepCount'        => 0,
                        'emailMsg'         => null,
                        'activation_alert' => 0,
                        'alert_70'         => 0,
                        'alert_100'        => 0,
                        'alert_data_70'    => 0,
                        'alert_data_100'   => 0,
                        'alert_bonus_70'   => 0,
                        'alert_bonus_100'  => 0,
                        'alert_tt_70'      => 0,
                        'alert_tt_100'     => 0,
                        'alert_tt_in_70'   => 0,
                        'alert_tt_in_100'  => 0,
                        'alert_tt_out_70'  => 0,
                        'alert_tt_out_100' => 0,
                        'alert_expiry'     => 0,
                        // freeEsim promo — free item has no bonus data
                        'bonus_data'       => 0,
                        'bonus_type'       => '',
                        'promocode'        => $this->appliedPromo['code'] ?? '',
                        'network'          => '',
                        'lang'             => app()->getLocale(),
                        'source'           => 'free_promo',
                        'esim_status'      => '',
                        'last_location'    => '',
                    ]);

                    $insertedOrderIds[] = $newId;

                    Log::info('[Checkout] Free eSIM order row inserted', [
                        'new_order_id' => $newId,
                        'master_uid'   => $orderId,
                    ]);
                }
            }

            if (empty($insertedOrderIds)) {
                throw new \Exception('No order rows inserted — cart was empty during handleFreePromoSuccess');
            }

            $order->update(['paymentStatus' => 'Paid']);

            if (!empty($this->appliedPromo)) {
                app(PromoService::class)->recordRedemption(
                    promo:     $this->appliedPromo,
                    userId:    $user->id,
                    orderId:   $insertedOrderIds[0],
                    masterUid: (string) $orderId,
                );
            }

            CartService::clear();
            $this->currentOrderId = null;
            DB::commit();

            Log::info('[Checkout] Free promo eSIM processed successfully', [
                'master_uid'  => $orderId,
                'order_ids'   => $insertedOrderIds,
                'promo_code'  => $this->appliedPromo['code'] ?? 'none',
            ]);

            foreach ($insertedOrderIds as $newOrderId) {
                ProcessEsimActivation::dispatch(
                    orderId:   (int) $newOrderId,
                    masterUid: (string) $orderId,
                    userId:    (int) $user->id,
                )->onQueue('esim');

                Log::info('[Checkout] ProcessEsimActivation dispatched for free eSIM', [
                    'order_id'   => $newOrderId,
                    'master_uid' => $orderId,
                ]);
            }

            $this->appliedPromo   = [];
            $this->discountAmount = 0;

            $this->dispatch('toast', type: 'success', message: 'Your free eSIM is being activated!');
            $firstId = $insertedOrderIds[0] ?? null;
            $this->redirect($firstId ? route('orders.detail', $firstId) : route('user.orders'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Checkout] handleFreePromoSuccess FAILED', [
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
                'order_id' => $orderId,
                'cart'     => $this->cart,
                'promo'    => $this->appliedPromo,
            ]);
            $this->dispatch('toast', type: 'error', message: 'Free eSIM processing failed. Please contact support.');
        }
    }

    public function handlePaymentSuccess(string $intentId, AirwallexService $service): void
    {
        DB::beginTransaction();
        try {
            $orderId = $this->currentOrderId;
            if (!$orderId) {
                throw new \Exception('Order ID missing from component state');
            }

            $order = OrdersInitiated::findOrFail($orderId);

            if ($order->paymentStatus === 'Paid') {
                DB::commit();
                $this->dispatch('toast', type: 'success', message: 'Payment already completed!');
                $this->redirect(route('user.orders'));
                return;
            }

            $intent = $service->getPaymentIntent($intentId);
            $status = $intent['status'] ?? '';

            if (!in_array($status, ['SUCCEEDED', 'REQUIRES_CAPTURE'])) {
                throw new \Exception('Payment not successful. Status: ' . $status);
            }

            $expectedAmount = $this->grandTotal;
            $paidAmount     = round((float) ($intent['amount'] ?? 0), 2);

            if ($expectedAmount > 0 && $paidAmount !== $expectedAmount) {
                throw new \Exception("Amount mismatch: expected {$expectedAmount}, got {$paidAmount}");
            }

            $paymentMethodUsed = $intent['payment_method_type'] ?? $this->paymentMethod ?? 'unknown';
            $user              = Auth::user();

            $this->syncConsentAfterPayment($intent, $service, $orderId, $paidAmount);

            $insertedOrderIds = [];
            $isRecharge       = $this->isRechargeOrder();
            $isB1G1           = ($this->appliedPromo['type'] ?? '') === 'buy1get1';

            // ── bonus_data sirf PEHLE item pe lagega — baaki sab pe 0 ──────────
            // Yeh flag ensure karta hai ki multi-item cart mein bonus ek hi
            // order row mein save ho, chahe promo type koi bhi ho.
            $bonusApplied = false;

            foreach ($this->cart as $item) {
                $qty = (int) ($item['quantity'] ?? 1);

                $hasTalkTime  = !empty($item['addons']['talk_time']['enabled']);
                $hasAutoTopup = !empty($item['addons']['auto_topup']['enabled']);
                $Mins         = $hasTalkTime ? 100 : 0;
                $autorenew    = $hasAutoTopup ? '1' : '0';
                $ttPrice      = $hasTalkTime ? (float) ($item['addons']['talk_time']['price'] ?? 0) : 0;

                if (($this->appliedPromo['type'] ?? '') === 'discount' && empty($item['is_promo_free'])) {
                    $discountMul = 1 - ($this->appliedPromo['discount'] / 100);
                    $baseCost    = round(($item['price'] ?? 0) * $discountMul, 4);
                    $perCost     = $baseCost + $ttPrice;
                } else {
                    $perCost = $qty > 0 ? round(($item['total'] ?? 0) / $qty, 4) : 0;
                }

                $plan        = DB::table('plans')->where('id', $item['plan_id'])->first();
                $GB          = $plan?->GB      ?? 0;
                $SMS         = $plan?->SMS     ?? 0;
                $Days        = $plan?->Days    ?? 0;
                $planMoniker = $plan?->Moniker ?? '';

                $orderType     = $item['order_type']        ?? 'newsim';
                $rechargeIccid = $item['recharge_iccid']    ?? '';
                $rechargeOldId = $item['recharge_order_id'] ?? null;

                $existingSubscriberId = '';
                $existingInventoryId  = null;

                if ($orderType === 'recharge' && $rechargeOldId) {
                    $orig = DB::table('orders')
                        ->where('id', $rechargeOldId)
                        ->select('subscriberId', 'inventoryId')
                        ->first();

                    $existingSubscriberId = $orig->subscriberId ?? '';
                    $existingInventoryId  = $orig->inventoryId  ?? null;

                    Log::info('[Checkout] Recharge — copied from old order', [
                        'old_order_id' => $rechargeOldId,
                        'subscriberId' => $existingSubscriberId,
                        'inventoryId'  => $existingInventoryId,
                    ]);
                }

                for ($i = 0; $i < $qty; $i++) {
                    $isFreeB1G1  = $isB1G1 && isset($item['b1g1_qty']) && $i >= (int) $item['b1g1_qty'];
                    $isFreePromo = !empty($item['is_promo_free']);
                    $isFreeRow   = $isFreeB1G1 || $isFreePromo;

                    $rowCost      = $isFreeRow  ? 0.00 : $perCost;
                    $rowMins      = $isFreeB1G1 ? 0    : $Mins;
                    $rowAutorenew = $isFreeB1G1 ? '0'  : $autorenew;

                    // ── bonus_data: sirf pehli baar is_bonus_item wale item pe lagao ──
                    $isBonusRow = !empty($item['is_bonus_item']) && !$bonusApplied;

                    $newId = DB::table('orders')->insertGetId([
                        'userid'           => $user->id,
                        'email'            => $user->email,
                        'plan_id'          => $item['plan_id'],
                        'plan_moniker'     => $planMoniker,
                        'customer_group'   => 'customer',
                        'GB'               => $GB,
                        'add_GB'           => '',
                        'Mins'             => $rowMins,
                        'SMS'              => $SMS,
                        'Days'             => $Days,
                        'esimLive'         => '0',
                        'autorenew'        => $rowAutorenew,
                        'orderType'        => $orderType,
                        'USD'              => $rowCost,
                        'status'           => 'IN PROGRESS',
                        'paymentStatus'    => 'Paid',
                        'transId'          => $intentId,
                        'transCode'        => '',
                        'authCode'         => '',
                        'msgCode'          => '',
                        'desc'             => 'airwallex_' . $paymentMethodUsed,
                        'date'             => now(),
                        'plan_start_date'  => '0000-00-00 00:00:00',
                        'plan_end_date'    => '0000-00-00 00:00:00',
                        'reclaimDate'      => null,
                        'loc_update_at'    => now(),
                        'activationFrom'   => 'WEBSITE',
                        'activationBy'     => $user->id,
                        'activationName'   => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')),
                        'profileId'        => '',
                        'paymentProfileId' => '',
                        'inventoryId'      => $existingInventoryId,
                        'msisdn'           => $isRecharge ? ($rechargeIccid ?? '') : '',
                        'customerid'       => '',
                        'subscriberId'     => $existingSubscriberId,
                        'my_uid'           => $orderId,
                        'apiRequest'       => '',
                        'apiResponse'      => '',
                        'apiDetails'       => '',
                        'reclaimApi'       => '',
                        'stepCount'        => 0,
                        'emailMsg'         => null,
                        'activation_alert' => 0,
                        'alert_70'         => 0,
                        'alert_100'        => 0,
                        'alert_data_70'    => 0,
                        'alert_data_100'   => 0,
                        'alert_bonus_70'   => 0,
                        'alert_bonus_100'  => 0,
                        'alert_tt_70'      => 0,
                        'alert_tt_100'     => 0,
                        'alert_tt_in_70'   => 0,
                        'alert_tt_in_100'  => 0,
                        'alert_tt_out_70'  => 0,
                        'alert_tt_out_100' => 0,
                        'alert_expiry'     => 0,
                        // ── bonus_data sirf pehle flagged item pe, baaki sab 0 ──
                        'bonus_data'       => $isBonusRow ? ($this->appliedPromo['amount'] ?? 0) : 0,
                        'bonus_type'       => $isBonusRow ? ($this->appliedPromo['type']   ?? '') : '',
                        'promocode'        => $this->appliedPromo['code'] ?? '',
                        'network'          => '',
                        'lang'             => app()->getLocale(),
                        'source'           => 'airwallex',
                        'esim_status'      => '',
                        'last_location'    => '',
                    ]);

                    // ── Flag flip: bonus pehle item ke baad lock ho jaata hai ──────
                    if ($isBonusRow) {
                        $bonusApplied = true;
                    }

                    $insertedOrderIds[] = [
                        'id'         => $newId,
                        'order_type' => $orderType,
                        'iccid'      => $rechargeIccid,
                        'old_order'  => $rechargeOldId,
                    ];
                }
            }

            $order->update(['paymentStatus' => 'Paid', 'transId' => $intentId]);

            if (!empty($this->appliedPromo)) {
                app(PromoService::class)->recordRedemption(
                    promo:     $this->appliedPromo,
                    userId:    $user->id,
                    orderId:   $insertedOrderIds[0]['id'],
                    masterUid: (string) $orderId,
                );
            }

            CartService::clear();
            $this->currentOrderId = null;
            DB::commit();

            Log::info('[Checkout] Payment completed', [
                'master_uid'   => $orderId,
                'intent_id'    => $intentId,
                'amount'       => $paidAmount,
                'order_count'  => count($insertedOrderIds),
                'is_recharge'  => $isRecharge,
                'promo_type'   => $this->appliedPromo['type'] ?? 'none',
                'bonus_applied'=> $bonusApplied,
            ]);

            foreach ($insertedOrderIds as $row) {
                if ($row['order_type'] === 'recharge') {
                    ProcessEsimRecharge::dispatch(
                        orderId:   (int) $row['id'],
                        masterUid: (string) $orderId,
                        userId:    (int) $user->id,
                        iccid:     (string) $row['iccid'],
                    )->onQueue('esim');
                } else {
                    ProcessEsimActivation::dispatch(
                        orderId:   (int) $row['id'],
                        masterUid: (string) $orderId,
                        userId:    (int) $user->id,
                    )->onQueue('esim');
                }
            }

            $this->appliedPromo   = [];
            $this->discountAmount = 0;
            $this->clientSecret   = null;
            $this->intentId       = null;

            $this->dispatch('toast', type: 'success', message: 'Payment successful! Your eSIM is being activated.');
            $firstId = $insertedOrderIds[0]['id'] ?? null;
            $this->redirect($firstId ? route('orders.detail', $firstId) : route('user.orders'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Checkout] handlePaymentSuccess failed', [
                'error'     => $e->getMessage(),
                'intent_id' => $intentId,
            ]);
            $this->dispatch('toast', type: 'error', message: 'Payment verification failed. Please contact support.');
        }
    }

    private function syncConsentAfterPayment(array $intent, AirwallexService $service, int $orderId, float $paidAmount): void
    {
        try {
            $consentId = $intent['payment_consent_id'] ?? null;
            if (!$consentId) return;

            $row = DB::table('payment_profile_log')
                ->where('userId', Auth::id())
                ->where('consent_id', $consentId)
                ->first();

            if (!$row) return;

            $remote = $service->getPaymentConsent($consentId);
            $card   = $remote['payment_method']['card'] ?? [];

            $hasDefault = DB::table('payment_profile_log')
                ->where('userId', Auth::id())
                ->where('consent_status', 'VERIFIED')
                ->where('is_default', 1)
                ->exists();

            DB::table('payment_profile_log')
                ->where('id', $row->id)
                ->update([
                    'consent_status' => 'VERIFIED',
                    'brand'          => $card['brand']        ?? null,
                    'last4'          => $card['last4']        ?? null,
                    'expiry_month'   => $card['expiry_month'] ?? null,
                    'expiry_year'    => $card['expiry_year']  ?? null,
                    'is_default'     => $hasDefault ? 0 : 1,
                    'order_total'    => $paidAmount,
                ]);

            if (!$hasDefault) {
                DB::table('users')
                    ->where('id', Auth::id())
                    ->update(['default_payment_method_id' => $consentId]);
            }

        } catch (\Exception $e) {
            Log::warning('[Checkout] syncConsentAfterPayment failed', ['error' => $e->getMessage()]);
        }
    }

    public function redirectToLogin(): void
    {
        session(['redirect_after_login' => 'checkout']);
        $promoFromUrl = request()->query('promo');
        if ($promoFromUrl) {
            session(['pending_promo' => strtoupper(trim($promoFromUrl))]);
        }
        $this->redirect(route('login'));
    }

    public function redirectToGoogle(): void
    {
        if (!empty($this->promoCode)) {
            session(['pending_promo' => strtoupper(trim($this->promoCode))]);
        }
        session(['redirect_after_login' => 'checkout']);
        $this->redirect(
            \Laravel\Socialite\Facades\Socialite::driver('google')->redirect()->getTargetUrl()
        );
    }

    public function redirectToApple(): void
    {
        if (!empty($this->promoCode)) {
            session(['pending_promo' => strtoupper(trim($this->promoCode))]);
        }
        session(['redirect_after_login' => 'checkout']);
        $this->redirect(
            \Laravel\Socialite\Facades\Socialite::driver('apple')->redirect()->getTargetUrl()
        );
    }

    public function render()
    {
        return view('livewire.checkout')->layout('layouts.app');
    }
}