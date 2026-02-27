<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CartService;
use App\Services\AirwallexService;
use App\Jobs\ProcessEsimActivation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrdersInitiated;

class Checkout extends Component
{
    public array $cart = [];
    public float $grandTotal = 0;
    public array $groupedCart = [];
    public bool $autoTopupEnabled = false;
    public bool $isGuest = true;
    public ?string $clientSecret = null;
    public ?string $intentId = null;
    public string $paymentMethod = 'card';
    public ?int $currentOrderId = null; // ✅ replaces session()

    protected $listeners = [
        'cart-sync' => 'refreshTotals',
    ];

    public function mount(): void
    {
        $this->isGuest = !Auth::check();
        $this->loadCart();
    }

    public function refreshTotals(): void
    {
        $this->loadCart();
    }

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

    // ─────────────────────────────────────────────────────────────────────────
    //  Step 1: Pay Now — DB record + Airwallex PaymentIntent create
    // ─────────────────────────────────────────────────────────────────────────

    public function payNow(AirwallexService $service): void
    {
        if ($this->isGuest) {
            $this->dispatch('toast', type: 'error', message: 'Please login to continue.');
            return;
        }

        // Validate allowed methods
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
            $order = OrdersInitiated::create([
                'userid'        => Auth::id(),
                'usd'           => $amount,
                'paymentStatus' => 'Pending',
                'custom'        => 'checkout',
            ]);

            Log::info('[Checkout] Order Initiated', [
                'order_id' => $order->id,
                'amount'   => $amount,
                'method'   => $this->paymentMethod,
            ]);

            $intent = $service->createPaymentIntent(
                amount:  $amount,
                orderId: (string) $order->id
            );

            $order->update(['transId' => $intent['id']]);

            // ✅ Store in Livewire property — NOT session
            $this->currentOrderId = $order->id;

            DB::commit();

            $this->intentId     = $intent['id'];
            $this->clientSecret = $intent['client_secret'];

            $this->dispatch(
                'startPayment',
                intentId:     $this->intentId,
                clientSecret: $this->clientSecret,
                method:       $this->paymentMethod,
            );

        } catch (\Exception $e) {
            DB::rollBack();
            app(AirwallexService::class)->clearTokenCache();
            Log::error('[Checkout] payNow Failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Payment initialization failed. Please try again.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Step 2: Payment verify — orders insert + eSIM job dispatch
    // ─────────────────────────────────────────────────────────────────────────

    public function handlePaymentSuccess(string $intentId, AirwallexService $service): void
    {
        DB::beginTransaction();

        try {
            // ✅ Read from Livewire property — NOT session
            $orderId = $this->currentOrderId;

            if (!$orderId) {
                throw new \Exception('Order ID missing from component state');
            }

            $order = OrdersInitiated::findOrFail($orderId);

            // Idempotency — already paid, skip
            if ($order->paymentStatus === 'Paid') {
                DB::commit();
                $this->dispatch('toast', type: 'success', message: 'Payment already completed!');
                $this->redirect(route('user.orders'));
                return;
            }

            // ✅ Server-side verify — never trust JS
            $intent = $service->getPaymentIntent($intentId);
            $status = $intent['status'] ?? '';

            if (!in_array($status, ['SUCCEEDED', 'REQUIRES_CAPTURE'])) {
                throw new \Exception('Payment not successful. Status: ' . $status);
            }

            // Amount check
            $expectedAmount = round(collect($this->cart)->sum('total'), 2);
            $paidAmount     = round((float) ($intent['amount'] ?? 0), 2);

            if ($paidAmount !== $expectedAmount) {
                Log::warning('[Checkout] Amount mismatch', [
                    'expected' => $expectedAmount,
                    'paid'     => $paidAmount,
                ]);
                throw new \Exception("Amount mismatch: expected {$expectedAmount}, got {$paidAmount}");
            }

            $paymentMethodUsed = $intent['payment_method_type'] ?? $this->paymentMethod ?? 'unknown';

            // payment_profile_log insert
            DB::table('payment_profile_log')->insert([
                'userId'           => Auth::id(),
                'profileId'        => '0',
                'paymentProfileId' => '0',
                'orderId'          => $orderId,
                'order_total'      => $paidAmount,
                'creationdate'     => now(),
            ]);

            $user = \App\Models\User::findOrFail(Auth::id());

            $insertedOrderIds = [];

            foreach ($this->cart as $item) {
                $qty     = (int) ($item['quantity'] ?? 1);
                $perCost = $qty > 0 ? round(($item['total'] ?? 0) / $qty, 4) : 0;
                $Mins    = !empty($item['addons']['talk_time']['enabled']) ? 100 : 0;
                $autorenew = !empty($item['addons']['auto_topup']['enabled']) ? '1' : '0';

                $plan       = DB::table('plans')->where('id', $item['plan_id'])->first();
                $GB         = $plan?->GB ?? 0;
                $SMS        = $plan?->SMS ?? 0;
                $Days       = $plan?->Days ?? 0;
                $planMoniker = $plan?->Moniker ?? '';

                for ($i = 0; $i < $qty; $i++) {
                    $newId = DB::table('orders')->insertGetId([
                        'userid'           => Auth::id(),
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
                        'orderType'        => 'newsim',
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
                        'activationBy'     => Auth::id(),
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

                    $insertedOrderIds[] = $newId;
                }
            }

            $order->update([
                'paymentStatus' => 'Paid',
                'transId'       => $intentId,
            ]);

            CartService::clear();

            // ✅ Clear Livewire property — NOT session->forget()
            $this->currentOrderId = null;

            DB::commit();

            Log::info('[Checkout] Payment Completed', [
                'master_uid'  => $orderId,
                'intent_id'   => $intentId,
                'amount'      => $paidAmount,
                'method'      => $paymentMethodUsed,
                'order_count' => count($insertedOrderIds),
            ]);

            // Dispatch eSIM activation jobs
            foreach ($insertedOrderIds as $newOrderId) {
                ProcessEsimActivation::dispatch(
                    orderId:   (int) $newOrderId,
                    masterUid: (string) $orderId,
                    userId:    (int) Auth::id(),
                )->onQueue('esim');
            }

            $this->clientSecret = null;
            $this->intentId     = null;

            $this->dispatch('toast', type: 'success', message: 'Payment successful! Your eSIM is being activated.');
            $this->redirect(route('user.orders'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Checkout] handlePaymentSuccess Failed', [
                'error'     => $e->getMessage(),
                'intent_id' => $intentId,
            ]);
            $this->dispatch('toast', type: 'error', message: 'Payment verification failed. Please contact support.');
        }
    }

    public function render()
    {
        return view('livewire.checkout')->layout('layouts.app');
    }

    private function loadCart(): void
    {
        $this->cart        = CartService::get();
        $this->grandTotal  = round(collect($this->cart)->sum('total'), 2);
        $this->groupedCart = collect($this->cart)->groupBy('zone_id')->toArray();
    }
}