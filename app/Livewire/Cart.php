<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;
use Throwable;

class Cart extends Component
{
    public array $cart = [];
    public bool $consentAccepted = false;
    public string $errorMessage = '';
   public bool $hasBonusPromo = false;
public bool $hasBOGOPromo  = false;
public string $promoBannerText = '';

    protected $listeners = [
        'cart-sync' => 'syncCart',
        'remove-item' => 'removeItem',
    ];

    public function mount(): void
    {
        $this->cart = CartService::get();
         // ── Promo indicator ──────────────────────────────────────────
   $this->hasBonusPromo = session('pending_promo') === 'NORSETEST';
    $this->hasBOGOPromo  = session('pending_promo') === 'NORSEBOGO';

    if ($this->hasBonusPromo) {
        $this->promoBannerText = '+2GB bonus data will be added to your plan on activation!';
    }
    if ($this->hasBOGOPromo) {
        $this->promoBannerText = 'Buy 1 Get 1 Free active! You\'ll get 2 eSIMs for the price of 1 at checkout.';
    }
    }

    /* ===============================
     | SYNC CART (PLAN / ADDON UPDATE)
     =============================== */
    public function syncCart(?string $key = null, array $data = []): void
    {
        if (!$key || empty($data)) {
            return;
        }

        try {
            CartService::update($key, $data);
            $this->cart = CartService::get();

            $this->dispatch(
                'toast',
                type: 'success',
                message: 'Cart updated successfully'
            );

        } catch (Throwable $e) {

            logger()->error('Cart sync failed', [
                'key' => $key,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Unable to update cart'
            );
        }
    }

    /* ===============================
     | REMOVE ITEM
     =============================== */
   public function removeItem(string $key): void
{
    try {
        CartService::remove($key);
        $this->cart = CartService::get();

        // Cart empty ho gayi toh promo bhi clear karo
       if (empty($this->cart)) {
    session()->forget('pending_promo');
    $this->hasBonusPromo   = false;
    $this->hasBOGOPromo    = false;
    $this->promoBannerText = '';
}
        $this->dispatch('toast', type: 'success', message: 'Item removed from cart');

    } catch (Throwable $e) {
        logger()->error('Remove item failed', ['key' => $key, 'error' => $e->getMessage()]);
        $this->dispatch('toast', type: 'error', message: 'Unable to remove item');
    }
}

    /* ===============================
     | GO TO CHECKOUT
     ===============================
     | 🔴 UPDATED:
     | - Login check YAHAN SE HATA DIYA
     | - Guest user bhi checkout page dekh sakta hai
     | - Login handling checkout component karega
     =============================== */
    public function goToCheckout()
    {
        $this->cart = CartService::get();

        if (empty($this->cart)) {
            $this->errorMessage = 'Your cart is empty.';
            return;
        }
        
        if (!$this->consentAccepted) {
            throw ValidationException::withMessages([
                'consentAccepted' => 'Please accept the eSIM terms to continue.'
            ]);
        }
        //  ALWAYS redirect to checkout
        return redirect()->route('checkout');
    }

    public function render()
    {
        return view('livewire.cart')
            ->layout('layouts.app');
    }
}
