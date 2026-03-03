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
    public array  $cart          = [];
    public float  $grandTotal    = 0;
    public array  $groupedCart   = [];
    public bool   $isGuest       = true;
    public ?string $clientSecret  = null;
    public ?string $intentId      = null;
    public string  $paymentMethod = 'card';
    public ?int    $currentOrderId = null;

    // ── Saved card state ──────────────────────────────────────────────────
    public bool    $saveCard          = false;
    public ?string $selectedConsentId = null;
    public bool    $usingSavedCard    = false;
    public array   $savedCards        = [];

    // ── PROMO STATE ───────────────────────────────────────────────────────
    // promoCode      → wire:model.defer se input field se bind hota hai
    // appliedPromo   → validate() ke baad fill hota hai; [] = no promo
    // promoError     → input ke neeche red text mein dikhta hai
    // discountAmount → sirf 'discount' type pe dollar savings; baki 0
    public string  $promoCode      = '';
    public array   $appliedPromo   = [];
    public ?string $promoError     = null;
    public float   $discountAmount = 0;
    // ─────────────────────────────────────────────────────────────────────

    protected $listeners = ['cart-sync' => 'refreshTotals'];

    public function mount(): void
    {
        $this->isGuest = !Auth::check();
        $this->loadCart();
        if (!$this->isGuest) $this->loadSavedCards();
    }

    public function refreshTotals(): void
    {
        $this->loadCart();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Cart helpers
    // ─────────────────────────────────────────────────────────────────────

    private function loadCart(): void
    {
        $this->cart        = CartService::get();
        $this->grandTotal  = round(collect($this->cart)->sum('total'), 2);
        $this->groupedCart = collect($this->cart)->groupBy('zone_id')->toArray();
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

    // ─────────────────────────────────────────────────────────────────────
    //  Saved card selection
    // ─────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────
    //  PROMO — Apply
    // ─────────────────────────────────────────────────────────────────────
    //
    // Flow:
    //   1. Input sanitize
    //   2. PromoService::validate() — 5 step check (zero DB writes)
    //   3. Fail  → $promoError set karo, return
    //   4. Success → $appliedPromo store, applyToState() se cart/total mutate
    //
    // DB write yahan NAHI hota.
    // Sirf handlePaymentSuccess() mein confirmed payment ke baad hota hai.

    public function applyPromo(PromoService $promoService): void
    {
        $this->promoError = null;

        if (empty(trim($this->promoCode))) {
            $this->promoError = 'Please enter a promo code.';
            return;
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

        // Cart / grandTotal memory mein mutate karo (no DB)
        $promoService->applyToState($this, $result['promo']);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PROMO — Remove
    // ─────────────────────────────────────────────────────────────────────
    //
    // applyToState() ke SABHI mutations undo karo:
    //   freeEsim  → is_promo_free=true items cart se nikalo
    //   buy1get1  → b1g1_qty se original quantity restore karo
    //   discount  → loadCart() se grandTotal recalculate (original prices)
    //   bonusData → koi cart mutation nahi tha, kuch undo nahi

    public function removePromo(): void
    {
        if (($this->appliedPromo['type'] ?? '') === 'freeEsim') {
            $this->cart = array_values(
                array_filter($this->cart, fn($i) => empty($i['is_promo_free']))
            );
        }

        if (($this->appliedPromo['type'] ?? '') === 'buy1get1') {
            // Step 1: FREE injected copies hatao (qty=1 case + different plans case)
            $this->cart = array_values(
                array_filter($this->cart, fn($i) => empty($i['is_promo_free']))
            );
            // Step 2: qty>=2 case — b1g1_qty clear, total restore karo original prices se
            foreach ($this->cart as &$item) {
                if (isset($item['b1g1_qty'])) {
                    $item['total'] = round(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2);
                    unset($item['b1g1_qty']);
                }
            }
            unset($item);
        }

        // bonusData flag clear karo
        foreach ($this->cart as &$item) {
            unset($item['is_bonus_item']);
        }
        unset($item);

        $this->appliedPromo   = [];
        $this->promoCode      = '';
        $this->promoError     = null;
        $this->discountAmount = 0;

        // Original cart prices se grandTotal recalculate
        $this->loadCart();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Pay Now
    // ─────────────────────────────────────────────────────────────────────
    //
    // Jab tak payNow() call hota hai, $this->grandTotal already mutated hai
    // agar discount promo apply hai.
    // Airwallex ko hamesha final (discounted) amount milta hai.

    public function payNow(AirwallexService $service): void
    {
        if ($this->isGuest) {
            $this->dispatch('toast', type: 'error', message: 'Please login to continue.');
            return;
        }

        if ($this->usingSavedCard && $this->selectedConsentId) {
            $this->payWithSavedCard($service);
            return;
        }

        $allowedMethods = ['card', 'paypal', 'googlepay', 'applepay'];
        if (!in_array($this->paymentMethod, $allowedMethods)) {
            $this->paymentMethod = 'card';
        }

        // grandTotal already discounted hai agar discount promo apply hai
        $amount = max(0, $this->grandTotal);

        if ($amount <= 0 && empty($this->appliedPromo)) {
            $this->dispatch('toast', type: 'error', message: 'Your cart is empty.');
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

            $this->dispatch('startPayment',
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

    // ─────────────────────────────────────────────────────────────────────
    //  Pay with saved card (MIT)
    // ─────────────────────────────────────────────────────────────────────

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
                $this->dispatch('startPayment',
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

    // ─────────────────────────────────────────────────────────────────────
    //  Payment Success
    // ─────────────────────────────────────────────────────────────────────
    //
    // PROMO integration points:
    //   [P1] expectedAmount = already-discounted grandTotal
    //   [P2] isB1G1 flag detect karo loop se pehle
    //   [P3] discount perCost = base discounted + talk_time full price
    //   [P4] rowCost  = 0 for free B1G1 / free promo rows
    //   [P5] rowMins  = 0 for free B1G1 rows (addons sirf paid row ko)
    //   [P6] rowAutorenew = '0' for free B1G1 rows
    //   [P7] bonus_data / bonus_type sirf bonusData type pe
    //   [P8] promocode hamesha write karo
    //   [P9] recordRedemption() orders insert ke BAAD, same transaction

    public function handlePaymentSuccess(string $intentId, AirwallexService $service): void
    {
        DB::beginTransaction();
        try {
            $orderId = $this->currentOrderId;
            if (!$orderId) throw new \Exception('Order ID missing from component state');

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

            // [P1] grandTotal already discounted hai — isi se compare karo
            $expectedAmount = $this->grandTotal;
            $paidAmount     = round((float) ($intent['amount'] ?? 0), 2);

            // Free-only cart (sirf freeEsim) pe amount check skip karo
            if ($expectedAmount > 0 && $paidAmount !== $expectedAmount) {
                throw new \Exception("Amount mismatch: expected {$expectedAmount}, got {$paidAmount}");
            }

            $paymentMethodUsed = $intent['payment_method_type'] ?? $this->paymentMethod ?? 'unknown';
            $user              = Auth::user();

            $this->syncConsentAfterPayment($intent, $service, $orderId, $paidAmount);

            $insertedOrderIds = [];
            $isRecharge       = $this->isRechargeOrder();

            // [P2] buy1get1 flag — foreach loop se pehle ek baar detect karo
            $isB1G1 = ($this->appliedPromo['type'] ?? '') === 'buy1get1';

            foreach ($this->cart as $item) {
                $qty = (int) ($item['quantity'] ?? 1);

                // ── Talk Time addon ───────────────────────────────────────
                // Mins = 100 agar talk_time enabled hai
                // Auto Topup = sirf flag, koi extra charge nahi
                $hasTalkTime  = !empty($item['addons']['talk_time']['enabled']);
                $hasAutoTopup = !empty($item['addons']['auto_topup']['enabled']);
                $Mins         = $hasTalkTime  ? 100 : 0;
                $autorenew    = $hasAutoTopup ? '1' : '0';

                // Talk time ka price (per unit)
                $ttPrice = $hasTalkTime
                    ? (float) ($item['addons']['talk_time']['price'] ?? 0)
                    : 0;

                // [P3] perCost calculation — promo type ke hisaab se
                if (($this->appliedPromo['type'] ?? '') === 'discount' && empty($item['is_promo_free'])) {
                    // discount: base plan pe discount lagao, talk_time full price
                    $discountMul  = 1 - ($this->appliedPromo['discount'] / 100);
                    $baseCost     = round(($item['price'] ?? 0) * $discountMul, 4);
                    $perCost      = $baseCost + $ttPrice;
                    //
                    // Example: plan=$29, discount=20%, TT=$8
                    //   baseCost = 29 * 0.80 = $23.20
                    //   perCost  = 23.20 + 8 = $31.20  ✓
                } else {
                    // Normal: total se per-unit nikalo
                    $perCost = $qty > 0 ? round(($item['total'] ?? 0) / $qty, 4) : 0;
                }

                // Plan DB row
                $plan        = DB::table('plans')->where('id', $item['plan_id'])->first();
                $GB          = $plan?->GB          ?? 0;
                $SMS         = $plan?->SMS         ?? 0;
                $Days        = $plan?->Days        ?? 0;
                $planMoniker = $plan?->Moniker     ?? '';

                // Recharge fields
                $orderType     = $item['order_type']      ?? 'newsim';
                $rechargeIccid = $item['recharge_iccid']  ?? '';
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

                // ── Per-row loop ──────────────────────────────────────────
                for ($i = 0; $i < $qty; $i++) {

                    // [P4] Kya yeh row free hai?
                    // buy1get1: b1g1_qty se upar ke rows free hain
                    // freeEsim: is_promo_free=true wala item hamesha free
                    $isFreeB1G1  = $isB1G1
                        && isset($item['b1g1_qty'])
                        && $i >= (int) $item['b1g1_qty'];

                    $isFreePromo = !empty($item['is_promo_free']);
                    $isFreeRow   = $isFreeB1G1 || $isFreePromo;

                    // [P4] rowCost
                    $rowCost = $isFreeRow ? 0.00 : $perCost;

                    // [P5] Talk Time — sirf paid row ko
                    // Free B1G1 row ko Mins=0 (addon nahi milega)
                    // freeEsim item mein addons[] empty hai — automatically 0
                    $rowMins = $isFreeB1G1 ? 0 : $Mins;

                    // [P6] Auto Topup — sirf paid row ko
                    // Free B1G1 row ko autorenew='0'
                    $rowAutorenew = $isFreeB1G1 ? '0' : $autorenew;

                    $newId = DB::table('orders')->insertGetId([
                        'userid'           => $user->id,
                        'email'            => $user->email,
                        'plan_id'          => $item['plan_id'],
                        'plan_moniker'     => $planMoniker,
                        'customer_group'   => 'customer',
                        'GB'               => $GB,
                        'add_GB'           => '',
                        'Mins'             => $rowMins,       // [P5]
                        'SMS'              => $SMS,
                        'Days'             => $Days,
                        'esimLive'         => '0',
                        'autorenew'        => $rowAutorenew,  // [P6]
                        'orderType'        => $orderType,
                        'USD'              => $rowCost,       // [P4]
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
                        'alert_70'         => 0,  'alert_100'       => 0,
                        'alert_data_70'    => 0,  'alert_data_100'  => 0,
                        'alert_bonus_70'   => 0,  'alert_bonus_100' => 0,
                        'alert_tt_70'      => 0,  'alert_tt_100'    => 0,
                        'alert_tt_in_70'   => 0,  'alert_tt_in_100' => 0,
                        'alert_tt_out_70'  => 0,  'alert_tt_out_100'=> 0,
                        'alert_expiry'     => 0,
                        'bonus_data'       => 0,  // overwritten below
                        'bonus_type'       => '',
                        'promocode'        => '',
                        'network'          => '',
                        'lang'             => app()->getLocale(),
                        'source'           => 'airwallex',
                        'esim_status'      => '',
                        'last_location'    => '',

                        // [P7] bonusData — sirf pehle item (is_bonus_item=true) pe apply
                        // PromoService::applyBonusData() ne pehle item ko mark kiya hai
                        // Baaki items pe bonus_data=0 — sirf ek eSIM pe bonus milega
                        'bonus_data'       => !empty($item['is_bonus_item'])
                                                ? ($this->appliedPromo['amount'] ?? 0) : 0,
                        'bonus_type'       => !empty($item['is_bonus_item']) ? 'promo' : '',

                        // [P8] promocode — hamesha write karo traceability ke liye
                        'promocode'        => $this->appliedPromo['code'] ?? '',
                    ]);

                    $insertedOrderIds[] = [
                        'id'         => $newId,
                        'order_type' => $orderType,
                        'iccid'      => $rechargeIccid,
                        'old_order'  => $rechargeOldId,
                    ];
                }
            }

            $order->update(['paymentStatus' => 'Paid', 'transId' => $intentId]);

            // [P9] Redemption record karo — orders insert ke BAAD, same transaction
            // Rollback hone pe ye bhi rollback hoga → failed payment pe promo waste nahi
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
                'master_uid'  => $orderId,
                'intent_id'   => $intentId,
                'amount'      => $paidAmount,
                'order_count' => count($insertedOrderIds),
                'is_recharge' => $isRecharge,
                'promo_type'  => $this->appliedPromo['type'] ?? 'none',
            ]);

            // ── Jobs dispatch ─────────────────────────────────────────────
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

            // Promo state reset after success
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

    // ─────────────────────────────────────────────────────────────────────
    //  Sync consent after payment
    // ─────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────
    //  Redirect helpers
    // ─────────────────────────────────────────────────────────────────────

    public function redirectToLogin(): void
    {
        session(['redirect_after_login' => 'checkout']);
        $this->redirect(route('login'));
    }

    public function redirectToGoogle(): void
    {
        session(['redirect_after_login' => 'checkout']);
        $this->redirect(
            \Laravel\Socialite\Facades\Socialite::driver('google')->redirect()->getTargetUrl()
        );
    }

    public function redirectToApple(): void
    {
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