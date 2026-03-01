<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEsimRecharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public int    $orderId,
        public string $masterUid,
        public int    $userId,
        public string $iccid,
    ) {}

public function handle(): void
{
    try {
        $service = app(\App\Services\EsimService::class);
        $service->rechargeEsim($this->orderId);
    } catch (\Throwable $e) {

        Log::error('[Recharge] FAILED', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);

        DB::table('orders')
            ->where('id', $this->orderId)
            ->update([
                'status'     => 'FAILED',
                'apiDetails' => $e->getMessage(),
            ]);

        throw $e;
    }
}
}