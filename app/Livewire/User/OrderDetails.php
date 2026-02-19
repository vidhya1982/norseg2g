<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Order;

class OrderDetails extends Component
{
    public Order $order;

    public function mount(Order $order)
    {
        // 🔐 Auth check
        abort_if(! auth()->check(), 403);

        // 🔐 Ownership check (CORRECT column)
        abort_if(
            (int) $order->userId !== (int) auth()->id(),
            403
        );

        // 🔄 Load relations (plan → zone, iccid)
        $this->order = $order->load([
            'plan.zone',
            'iccid',
        ]);
    }

    public function render()
    {
        return view('livewire.user.order-details')->layout('layouts.app');
    }
}
