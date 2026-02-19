<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Order;

class Orders extends Component
{
    public function toggleAutoTopup($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('userid', auth()->id())
            ->firstOrFail();

        $order->autorenew = !$order->autorenew;
        $order->save();
    }

    public function render()
    {
        // 🔥 ALL DATA (client-side pagination)
     $orders = Order::where('userid', auth()->id())
    ->with(['plan.zone'])   // 👈 THIS IS THE KEY
    ->orderByDesc('date')
    ->get();


        return view('livewire.user.orders', compact('orders'))
            ->layout('layouts.app');
    }
}
