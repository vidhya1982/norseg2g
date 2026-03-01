<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CartService;
use App\Services\AirwallexService;
use App\Jobs\ProcessEsimActivation;
use App\Jobs\ProcessEsimRecharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrdersInitiated;
use App\Models\PaymentProfileLog;

class Checkout extends Component
{
    public array   $cart           = [];
    public float   $grandTotal     = 0;
    public array   $groupedCart    = [];
    public bool    $isGuest        = true;
    public ?string $clientSecret   = null;
    public ?string $intentId       = null;
    public string  $paymentMethod  = 'card';
    public ?int    $currentOrderId = null;

    // ── Saved card state ─────────────────────────────────────────────────
    public bool    $saveCard          = false;
    public ?string $selectedConsentId = null;
    public bool    $usingSavedCard    = false;
    public array   $savedCards        = [];

    protected $listeners = [
        'cart-sync' => 'refreshTotals',
    ];

    public function mount(): void
    {
        $this->isGuest = !Auth::check();
        $this->loadCart();

        if (!$this->isGuest) {
            $this->loadSavedCards();
        }
    }

    public function refreshTotals(): void
    {
        $this->loadCart();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Helpers
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

    /**
     * Is this a recharge order? (cart item has order_type = 'recharge')
     */
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
    //  Pay Now — entry point
    // ─────────────────────────────────────────────────────────────────────

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

        $amount = round(collect($this->cart)->sum('total'), 2);
        if ($amount <= 0) {
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
        $amount = round(collect($this->cart)->sum('total'), 2);
        if ($amount <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Your cart is empty.');
            return;
        }

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
            $user = Auth::user();

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

            $expectedAmount = round(collect($this->cart)->sum('total'), 2);
            $paidAmount     = round((float) ($intent['amount'] ?? 0), 2);

            if ($paidAmount !== $expectedAmount) {
                throw new \Exception("Amount mismatch: expected {$expectedAmount}, got {$paidAmount}");
            }

            $paymentMethodUsed = $intent['payment_method_type'] ?? $this->paymentMethod ?? 'unknown';
            $user              = Auth::user();

            $this->syncConsentAfterPayment($intent, $service, $orderId, $paidAmount);

            $insertedOrderIds = [];
            $isRecharge       = $this->isRechargeOrder();

            foreach ($this->cart as $item) {
                $qty       = (int) ($item['quantity'] ?? 1);
                $perCost   = $qty > 0 ? round(($item['total'] ?? 0) / $qty, 4) : 0;
                $Mins      = !empty($item['addons']['talk_time']['enabled']) ? 100 : 0;
                $autorenew = !empty($item['addons']['auto_topup']['enabled']) ? '1' : '0';

                $plan        = DB::table('plans')->where('id', $item['plan_id'])->first();
                $GB          = $plan?->GB     ?? 0;
                $SMS         = $plan?->SMS    ?? 0;
                $Days        = $plan?->Days   ?? 0;
                $planMoniker = $plan?->Moniker ?? '';

                // Recharge-specific fields from cart
                $orderType     = $item['order_type']        ?? 'newsim';
                $rechargeIccid = $item['recharge_iccid']    ?? '';
                $rechargeOldId = $item['recharge_order_id'] ?? null;

                // ✅ FIX — Recharge ke liye old order se subscriberId + inventoryId copy karo
                $existingSubscriberId = '';
                $existingInventoryId  = null;

                if ($orderType === 'recharge' && $rechargeOldId) {
                    $originalOrder = DB::table('orders')
                        ->where('id', $rechargeOldId)
                        ->select('subscriberId', 'inventoryId')
                        ->first();

                    $existingSubscriberId = $originalOrder->subscriberId ?? '';
                    $existingInventoryId  = $originalOrder->inventoryId  ?? null;

                    Log::info('[Checkout] Recharge — copied from old order', [
                        'old_order_id' => $rechargeOldId,
                        'subscriberId' => $existingSubscriberId,
                        'inventoryId'  => $existingInventoryId,
                    ]);
                }

                for ($i = 0; $i < $qty; $i++) {
                    $newId = DB::table('orders')->insertGetId([
                        'userid'           => $user->id,
                        'email'            => $user->email,
                        'plan_id'          => $item['plan_id'],
                        'plan_moniker'     => $planMoniker,
                        'customer_group'   => 'customer',
                        'GB'               => $GB,
                        'add_GB'           => '',
                        'Mins'             => $Mins,
                        'SMS'              => $SMS,
                        'Days'             => $Days,
                        'esimLive'         => '0',
                        'autorenew'        => $autorenew,
                        'orderType'        => $orderType,
                        'USD'              => $perCost,
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
                        'inventoryId'      => $existingInventoryId,   // ✅ old order se copy
                        'msisdn'           => $isRecharge ? ($rechargeIccid ?? '') : '',
                        'customerid'       => '',
                        'subscriberId'     => $existingSubscriberId,  // ✅ old order se copy
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
                        'alert_tt_in_70'   => 0,
                        'alert_tt_in_100'  => 0,
                        'alert_tt_out_70'  => 0,
                        'alert_tt_out_100' => 0,
                        'alert_expiry'     => 0,
                        'alert_tt_100'     => 0,
                        'bonus_data'       => 0,
                        'bonus_type'       => '',
                        'promocode'        => '',
                        'network'          => '',
                        'lang'             => app()->getLocale(),
                        'source'           => 'airwallex',
                        'esim_status'      => '',
                        'last_location'    => '',
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
            CartService::clear();
            $this->currentOrderId = null;

            DB::commit();

            Log::info('[Checkout] Payment completed', [
                'master_uid'  => $orderId,
                'intent_id'   => $intentId,
                'amount'      => $paidAmount,
                'order_count' => count($insertedOrderIds),
                'is_recharge' => $isRecharge,
            ]);

            // ── Dispatch correct job per order ────────────────────────────
            foreach ($insertedOrderIds as $row) {
                if ($row['order_type'] === 'recharge') {
                    // ✅ Recharge job
                    ProcessEsimRecharge::dispatch(
                        orderId:   (int) $row['id'],
                        masterUid: (string) $orderId,
                        userId:    (int) $user->id,
                        iccid:     (string) $row['iccid'],
                    )->onQueue('esim');
                } else {
                    // ✅ New eSIM activation
                    ProcessEsimActivation::dispatch(
                        orderId:   (int) $row['id'],
                        masterUid: (string) $orderId,
                        userId:    (int) $user->id,
                    )->onQueue('esim');
                }
            }

            $this->clientSecret = null;
            $this->intentId     = null;

            $this->dispatch('toast', type: 'success', message: 'Payment successful! Your eSIM is being activated.');

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