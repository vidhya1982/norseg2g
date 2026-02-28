<?php

namespace App\Jobs;

use App\Services\AirwallexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoTopupPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    // Retry after: 5 min, 15 min, 30 min
    public function backoff(): array
    {
        return [300, 900, 1800];
    }

    public function __construct(
        public readonly int    $originalOrderId,  // Jis order ka data run out hua
        public readonly int    $userId,
        public readonly float  $amount,           // Plan ka cost (USD)
        public readonly int    $planId,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  Handle
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(AirwallexService $service): void
    {
        Log::info('[AutoTopup] Job Started', [
            'order_id' => $this->originalOrderId,
            'user_id'  => $this->userId,
            'amount'   => $this->amount,
            'plan_id'  => $this->planId,
        ]);

        // ── User + Saved Card Check ───────────────────────────────────────────
        $user = DB::table('users')->where('id', $this->userId)->first();

        if (!$user) {
            Log::error('[AutoTopup] User not found', ['user_id' => $this->userId]);
            $this->fail('User not found: ' . $this->userId);
            return;
        }

        if (!$user->airwallex_customer_id) {
            Log::warning('[AutoTopup] No Airwallex customer ID', ['user_id' => $this->userId]);
            $this->markOrderFailed('No saved payment profile');
            return;
        }

        if (!$user->default_payment_method_id) {
            Log::warning('[AutoTopup] No default payment method', ['user_id' => $this->userId]);
            $this->markOrderFailed('No default payment method saved');
            return;
        }

        // ── Verify Saved Card Exists ──────────────────────────────────────────
        $savedCard = DB::table('payment_profile_log')
            ->where('userId', $this->userId)
            ->where('payment_method_id', $user->default_payment_method_id)
            ->where('consent_status', 'VERIFIED')
            ->first();

        if (!$savedCard) {
            Log::warning('[AutoTopup] Saved card not verified', [
                'user_id'           => $this->userId,
                'payment_method_id' => $user->default_payment_method_id,
            ]);
            $this->markOrderFailed('Saved card not found or not verified');
            return;
        }

        // ── Idempotency Check ─────────────────────────────────────────────────
        $alreadyProcessed = DB::table('orders')
            ->where('my_uid', 'autotopup_' . $this->originalOrderId)
            ->where('paymentStatus', 'Paid')
            ->exists();

        if ($alreadyProcessed) {
            Log::info('[AutoTopup] Already processed', ['order_id' => $this->originalOrderId]);
            return;
        }

        try {
            // ── Step 1: Create PaymentIntent ──────────────────────────────────
            $masterUid = 'autotopup_' . $this->originalOrderId . '_' . time();

            $intent = $service->createPaymentIntent(
                amount:     $this->amount,
                orderId:    $masterUid,
                customerId: $user->airwallex_customer_id,
            );

            Log::info('[AutoTopup] Intent Created', [
                'intent_id' => $intent['id'],
                'amount'    => $this->amount,
            ]);

            // ── Step 2: Confirm — MIT (Merchant Initiated, no CVC needed) ─────
            $confirmed = $service->confirmPaymentIntentWithSavedCard(
                intentId:        $intent['id'],
                customerId:      $user->airwallex_customer_id,
                paymentMethodId: $user->default_payment_method_id,
                triggeredBy:     'merchant',       // ← MIT — user absent
                cvc:             null,              // ← CVC nahi chahiye MIT mein
                triggerReason:   'unscheduled',    // Auto-topup = unscheduled MIT
            );

            $status = $confirmed['status'] ?? '';

            if (!in_array($status, ['SUCCEEDED', 'REQUIRES_CAPTURE'])) {
                throw new \Exception('MIT payment not successful. Status: ' . $status);
            }

            // ── Step 3: Plan details load karo ───────────────────────────────
            $plan = DB::table('plans')->where('id', $this->planId)->first();

            if (!$plan) {
                throw new \Exception('Plan not found: ' . $this->planId);
            }

            $originalOrder = DB::table('orders')->where('id', $this->originalOrderId)->first();

            // ── Step 4: Recharge Order Insert ────────────────────────────────
            $newOrderId = DB::table('orders')->insertGetId([
                'userid'           => $this->userId,
                'email'            => $user->email,
                'plan_id'          => $this->planId,
                'plan_moniker'     => $plan->Moniker ?? '',
                'customer_group'   => 'customer',
                'GB'               => $plan->GB ?? 0,
                'add_GB'           => '',
                'Mins'             => $originalOrder?->Mins ?? 0,
                'SMS'              => $plan->SMS ?? 0,
                'Days'             => $plan->Days ?? 0,
                'esimLive'         => '0',
                'autorenew'        => '1',
                'orderType'        => 'autotopup',
                'USD'              => $this->amount,
                'status'           => 'IN PROGRESS',
                'paymentStatus'    => 'Paid',
                'transId'          => $intent['id'],
                'transCode'        => '',
                'authCode'         => '',
                'msgCode'          => '',
                'desc'             => 'airwallex_autotopup_mit',
                'date'             => now(),
                'plan_start_date'  => '0000-00-00 00:00:00',
                'plan_end_date'    => '0000-00-00 00:00:00',
                'reclaimDate'      => null,
                'loc_update_at'    => now(),
                'activationFrom'   => 'AUTO-TOPUP',
                'activationBy'     => $this->userId,
                'activationName'   => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')),
                'profileId'        => '',
                'paymentProfileId' => '',
                'inventoryId'      => null,
                'msisdn'           => $originalOrder?->msisdn ?? '',
                'customerid'       => $originalOrder?->customerid ?? '',
                'subscriberId'     => $originalOrder?->subscriberId ?? '',
                'my_uid'           => 'autotopup_' . $this->originalOrderId,
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
                'network'          => $originalOrder?->network ?? '',
                'lang'             => 'en',
                'source'           => 'airwallex_autotopup',
                'esim_status'      => '',
                'last_location'    => '',
            ]);

            // ── Step 5: payment_profile_log record ───────────────────────────
            DB::table('payment_profile_log')->insert([
                'userId'            => $this->userId,
                'profileId'         => '0',
                'paymentProfileId'  => '0',
                'orderId'           => $newOrderId,
                'order_total'       => $this->amount,
                'creationdate'      => now(),
                'payment_method_id' => $user->default_payment_method_id,
                'consent_status'    => 'VERIFIED',
                'brand'             => $savedCard->brand,
                'last4'             => $savedCard->last4,
                'expiry_month'      => $savedCard->expiry_month,
                'expiry_year'       => $savedCard->expiry_year,
                'is_default'        => 0,
            ]);

            Log::info('[AutoTopup] Payment Completed', [
                'original_order_id' => $this->originalOrderId,
                'new_order_id'      => $newOrderId,
                'intent_id'         => $intent['id'],
                'amount'            => $this->amount,
            ]);

            // ── Step 6: eSIM Activation Dispatch ─────────────────────────────
            ProcessEsimActivation::dispatch(
                orderId:   $newOrderId,
                masterUid: 'autotopup_' . $this->originalOrderId,
                userId:    $this->userId,
            )->onQueue('esim');

        } catch (\Exception $e) {
            Log::error('[AutoTopup] Payment Failed', [
                'order_id' => $this->originalOrderId,
                'user_id'  => $this->userId,
                'error'    => $e->getMessage(),
                'attempt'  => $this->attempts(),
            ]);

            // Last attempt pe mark failed
            if ($this->attempts() >= $this->tries) {
                $this->markOrderFailed($e->getMessage());
            }

            throw $e; // Queue retry ke liye
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Mark original order as auto-topup failed
    // ─────────────────────────────────────────────────────────────────────────

    private function markOrderFailed(string $reason): void
    {
        DB::table('orders')
            ->where('id', $this->originalOrderId)
            ->update([
                'autorenew' => '2', // 2 = failed, 1 = enabled, 0 = disabled
                'apiDetails' => 'AutoTopup failed: ' . $reason,
            ]);

        Log::error('[AutoTopup] Marked as Failed', [
            'order_id' => $this->originalOrderId,
            'reason'   => $reason,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Failed — all retries exhausted
    // ─────────────────────────────────────────────────────────────────────────

    public function failed(\Throwable $e): void
    {
        Log::critical('[AutoTopup] Job Permanently Failed', [
            'order_id' => $this->originalOrderId,
            'user_id'  => $this->userId,
            'error'    => $e->getMessage(),
        ]);

        $this->markOrderFailed('All retries exhausted: ' . $e->getMessage());
    }
}