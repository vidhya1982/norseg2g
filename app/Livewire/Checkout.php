<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class Checkout extends Component
{
    public array $cart = [];
    public float $grandTotal = 0;
    public array $groupedCart = [];
    public bool $autoTopupEnabled = false;
    public bool $isGuest = true; //  NEW: guest / logged-in state

    protected $listeners = [
        'cart-sync' => 'refreshTotals', //  cart updates reflected on checkout
    ];

    public function mount()
    {
        $this->isGuest = !Auth::check();

        $this->cart = CartService::get();
        $this->grandTotal = collect($this->cart)->sum('total');

        // ✅ GROUP CART BY ZONE
        $this->groupedCart = collect($this->cart)
            ->groupBy('zone_id')
            ->toArray();
    }

    public function refreshTotals()
    {
        $this->cart = CartService::get();
        $this->grandTotal = collect($this->cart)->sum('total');
    }


    public function redirectToLogin()
    {
        session(['redirect_after_login' => 'checkout']);
        return redirect()->route('login');
    }
    public function redirectToGoogle()
    {
        session(['redirect_after_login' => 'checkout']);

        return redirect()->away(
            \Laravel\Socialite\Facades\Socialite::driver('google')->redirect()->getTargetUrl()
        );
    }

    public function redirectToApple()
    {
        session(['redirect_after_login' => 'checkout']);

        return redirect()->away(
            \Laravel\Socialite\Facades\Socialite::driver('apple')->redirect()->getTargetUrl()
        );
    }


    public function render()
    {
        return view('livewire.checkout')
            ->layout('layouts.app');
    }
}
