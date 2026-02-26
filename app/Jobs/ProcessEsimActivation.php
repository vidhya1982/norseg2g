<?php

// ─────────────────────────────────────────────────────────────────────────────
//  FILE: app/Jobs/ProcessEsimActivation.php
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Jobs;

use App\Services\EsimService;
use App\Services\SendGridService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessEsimActivation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ── Retry config ──────────────────────────────────────────────────────────
    public int $tries   = 100;    // 3 baar try karega
    public int $backoff = 60;   // har retry se pehle 60 sec wait
    public int $timeout = 120;  // ek attempt max 2 min

    public function __construct(
        public readonly int    $orderId,    // orders.id
        public readonly string $masterUid,  // orders_initiated.id = orders.my_uid
        public readonly int    $userId,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  handle() — Queue worker yahan se start karta hai
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(EsimService $esimService): void
    {
        Log::info('[Job] ProcessEsimActivation START', [
            'order_id'   => $this->orderId,
            'master_uid' => $this->masterUid,
            'attempt'    => $this->attempts(),
            'max_tries'  => $this->tries,
        ]);

        $result = $esimService->activateNewEsim($this->orderId, $this->masterUid);

        // Agar success ya skip (already active) → job done
        if ($result['success'] ?? false) {
            Log::info('[Job] ProcessEsimActivation DONE', [
                'order_id' => $this->orderId,
                'skipped'  => $result['skipped'] ?? false,
            ]);
            return;
        }

        // Fail — retry baaki hai toh exception throw karo (Laravel auto-retry karega)
        $error = $result['error']    ?? 'Unknown error';
        $step  = $result['stepCount'] ?? 0;

        Log::error('[Job] ProcessEsimActivation FAILED', [
            'order_id' => $this->orderId,
            'step'     => $step,
            'attempt'  => $this->attempts(),
            'error'    => $error,
        ]);

        // Retry trigger — exception throw karo
        throw new \RuntimeException(
            "eSIM activation failed at step {$step}: {$error}"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  failed() — Saare retries exhaust hone ke baad Laravel yeh call karta hai
    // ─────────────────────────────────────────────────────────────────────────

    public function failed(\Throwable $exception): void
    {
        Log::critical('[Job] ProcessEsimActivation PERMANENTLY FAILED — all retries exhausted', [
            'order_id'  => $this->orderId,
            'master_uid' => $this->masterUid,
            'error'     => $exception->getMessage(),
        ]);

        // Order status FAILED mark karo
        DB::table('orders')
            ->where('id', $this->orderId)
            ->update(['status' => 'FAILED']);

        // Admin ko critical alert email
        try {
            SendGridService::send(
                config('services.esim.admin_email'),
                config('services.esim.template_admin_alert', 'd-96390228b0804796987dc58ebb5284b8'),
                [
                    'subject'  => " eSIM PERMANENTLY FAILED — Order #{$this->orderId}",
                    'function' => 'ProcessEsimActivation::failed() — all 3 retries exhausted',
                    'request'  => "Order ID: {$this->orderId} | Master UID: {$this->masterUid} | User ID: {$this->userId}",
                    'response' => $exception->getMessage(),
                    'note'     => 'Manual intervention required. Check failed_jobs table.',
                    'order_id' => $this->orderId,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[Job] Admin critical alert email failed', ['error' => $e->getMessage()]);
        }
    }
}