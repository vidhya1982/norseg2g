<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Order;
use App\Services\EsimService;
use Carbon\Carbon;

class Balance extends Component
{
    public Order $order;
    public array $balance = [];
    public bool $loading = true;
    public ?string $error = null;

    // ── Computed from balance data ────────────────────────────
    public float $dataTotal    = 0;
    public float $dataRem      = 0;
    public float $dataUsed     = 0;
    public int   $dataPct      = 0;
    public float $bonusTotal   = 0;
    public float $bonusRem     = 0;
    public int   $bonusPct     = 0;
    public int   $callOut      = 0;
    public int   $callOutTotal = 0;
    public int   $smsRem       = 0;
    public int   $smsTotal     = 0;
    public string $planName    = '—';
    public bool  $isActive     = false;
    public string $statusText  = '';
    public ?string $endTime    = null;
    public ?int  $daysLeft     = null;
    public string $expiryClass = 'ok';
    public string $expiryLabel = '';

    public function mount(int $id): void
    {
        $this->order = Order::where('id', $id)
            ->where('userId', auth()->id())
            ->firstOrFail();

        try {
            if (!$this->order->subscriberId) {
                throw new \Exception('eSIM not activated yet.');
            }
            if (!$this->order->msisdn) {
                throw new \Exception('MSISDN not assigned to this order.');
            }

            $service = app(EsimService::class);

            $this->balance = cache()->remember(
                'balance_' . $this->order->subscriberId,
                now()->addMinutes(5),
                fn() => $service->getSubscriberBalance($this->order->subscriberId)
            );

            $this->prepareDisplayData();

        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }

        $this->loading = false;
    }

    private function prepareDisplayData(): void
    {
        $b = $this->balance;

        // Data
        $this->dataTotal = (float) ($b['data_total_gb'] ?? 0);
        $this->dataRem   = (float) ($b['data_rem_gb']   ?? 0);
        $this->dataUsed  = max(0, round($this->dataTotal - $this->dataRem, 2));
        $this->dataPct   = $this->dataTotal > 0
            ? min(100, (int) round(($this->dataRem / $this->dataTotal) * 100))
            : 0;

        // Bonus
        $this->bonusTotal = (float) ($b['bonus_total_gb'] ?? 0);
        $this->bonusRem   = (float) ($b['bonus_rem_gb']   ?? 0);
        $this->bonusPct   = $this->bonusTotal > 0
            ? min(100, (int) round(($this->bonusRem / $this->bonusTotal) * 100))
            : 0;

        // Voice & SMS
        $this->callOut      = (int) ($b['call_out_rem']   ?? 0);
        $this->callOutTotal = (int) ($b['call_out_total'] ?? 0);
        $this->smsRem       = (int) ($b['sms_rem']        ?? 0);
        $this->smsTotal     = (int) ($b['sms_total']      ?? 0);

        // Plan & status
        $this->planName   = $b['plan_name'] ?? ($this->order->plan_moniker ?? '—');
        $this->isActive   = $this->order->status === 'ACTIVE';
        $this->statusText = $this->isActive ? 'Active' : ($this->order->status ?? 'Unknown');

        // Expiry
        $this->endTime = ($b['end_time'] ?? null);
        if ($this->endTime && $this->endTime !== '0000-00-00 00:00:00') {
            try {
                $exp = Carbon::parse($this->endTime);
                if ($exp->isPast()) {
                    $this->expiryClass = 'danger';
                    $this->expiryLabel = 'Expired';
                } else {
                    $this->daysLeft = (int) $exp->diffInDays(now());
                    if ($this->daysLeft <= 3) {
                        $this->expiryClass = 'danger';
                        $this->expiryLabel = $this->daysLeft . 'd left';
                    } elseif ($this->daysLeft <= 7) {
                        $this->expiryClass = 'warn';
                        $this->expiryLabel = $this->daysLeft . 'd left';
                    } else {
                        $this->expiryClass = 'ok';
                        $this->expiryLabel = $this->daysLeft . ' days left';
                    }
                }
            } catch (\Exception $e) {
                $this->expiryLabel = '';
            }
        }
    }

    public function render()
    {
        return view('livewire.user.balance')
            ->layout('layouts.app');
    }
}