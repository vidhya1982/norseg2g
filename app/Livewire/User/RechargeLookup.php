<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RechargeLookup extends Component
{
    public string  $search   = '';
    public ?string $errorMsg = null;

    public function findOrder(): void
    {
        $this->errorMsg = null;
        $input = trim($this->search);

        if (empty($input)) {
            $this->errorMsg = 'Please enter your ICCID or phone number.';
            return;
        }

        // ── Find ACTIVE order by ICCID or MSISDN ─────────────────────────
        $query = DB::table('orders as o')
            ->leftJoin('ICCID as i', 'i.id', '=', 'o.inventoryId')
            ->where('o.status', 'ACTIVE')
            ->where(function ($q) use ($input) {
                $q->where('i.ICCID', $input)
                  ->orWhere('o.msisdn', $input);
            });

        // Logged in — restrict to their own orders
        if (Auth::check()) {
            $query->where('o.userid', Auth::id());
        }

        $order = $query
            ->orderByDesc('o.id')
            ->select('o.id', 'o.msisdn', 'o.userid')
            ->first();

        if (!$order) {
            $this->errorMsg = 'No active eSIM found for this ICCID / phone number.';
            return;
        }

        // ── Guest → require login ─────────────────────────────────────────
        if (!Auth::check()) {
            session(['redirect_after_login' => route('orders.recharge', ['msisdn' => $order->msisdn])]);
            $this->redirect(route('login'));
            return;
        }

        // ✅ route name matches web.php — 'orders.recharge'
        $this->redirect(route('orders.recharge', ['msisdn' => $order->msisdn]));
    }

    public function render()
    {
        return view('livewire.user.recharge-lookup')
            ->layout('layouts.app');
    }
}