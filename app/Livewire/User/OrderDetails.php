<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Order;

class OrderDetails extends Component
{
    public Order $order;

   public function mount($id)
{
    abort_if(!auth()->check(), 403);

    $order = Order::where('id', $id)->firstOrFail();

    abort_if(
        (int) $order->userId !== (int) auth()->id(),
        403
    );

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
