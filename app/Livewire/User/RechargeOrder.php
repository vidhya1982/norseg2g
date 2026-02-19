<?php
namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Order;

class RechargeOrder extends Component
{
    public Order $order;
    public float $amount = 0;

    public function mount(Order $order)
    {
        abort_if($order->userId !== auth()->id(), 403);
        $this->order = $order;
    }

    public function recharge()
    {
        $this->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // 🔧 Example logic (adjust as per business rules)
        $this->order->USD += $this->amount;
        $this->order->save();

        session()->flash('success', 'Recharge successful!');
    }

    public function render()
    {
        return view('livewire.user.recharge-order')
            ->layout('layouts.app');
    }
}

