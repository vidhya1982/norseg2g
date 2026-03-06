{{--
|--------------------------------------------------------------------------
| CART PAGE
|--------------------------------------------------------------------------
| NOTE:
| - Cart page ALWAYS editable rahega
| - Login required yahan nahi hai
| - Guest user bhi quantity / addons change kar sakta hai
| - Checkout page par hi login restriction lagegi
|--------------------------------------------------------------------------
--}}

<div class="container-fluid page-background py-5" x-data x-init="$store.cart.init(@js($cart))">

    <div class="container cart-container">
@if($hasBonusPromo || $hasBOGOPromo)
<div class="promo-plans-banner">
    <div class="container">
        <div class="promo-banner-inner">
            <span class="promo-banner-icon">{{ $hasBOGOPromo ? '🎁' : '⚡' }}</span>
            <div>
                <strong>{{ $hasBOGOPromo ? 'Buy 1 Get 1 Free!' : 'Bonus Data Promo Active!' }}</strong>
                <p>{{ $promoBannerText }}</p>
            </div>
        </div>
    </div>
</div>
@endif
        <h4 class="mb-4"> Your Cart ({{ count($cart) }}) </h4>

        <div class="row">

            <!-- ================= LEFT COLUMN ================= -->
            <div class="col-lg-8 mb-3">

                @if(count($cart) === 0)

                    {{-- ================= EMPTY CART STATE ================= --}}
                    <div class="empty-cart-wrapper d-flex ">
                        <div class="empty-cart-card text-center p-5 rounded-4 bg-white shadow-sm">

                            <div class="empty-cart-icon mb-3">
                                <i class="fa-solid fa-cart-shopping text-primary"></i>
                            </div>

                            <h4 class="fw-bold mb-2">Your cart is empty</h4>

                            <p class="text-muted mb-4">
                                Looks like you haven’t added any eSIM plans yet.
                                Start exploring and stay connected worldwide
                            </p>

                            <a href="{{ url('/#topPlans') }}" class="ready-btn text-decoration-none">
                                Browse Plans
                            </a>
                        </div>
                    </div>

                @else

                    @foreach ($cart as $key => $item)
                        <!-- ================= ZONE SECTION ================= -->
                        <div class="cart-item mb-4">

                            <div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">
    @if(!empty($item['is_unlimited']))
        eSIM for {{ $item['days'] ?? '?' }} Days
    @else
        eSIM for {{ $item['gb'] ?? trim(explode(',', $item['plan_name'])[0]) }} GB
    @endif
</h5>

                                <div class="d-flex align-items-center gap-2">

                                    {{-- REMOVE ITEM (ALWAYS ENABLED ON CART PAGE) --}}
                                    <button class="btn btn-sm text-danger border-0" @click="
                                                        delete $store.cart.items['{{ $key }}'];
                                                        Livewire.dispatch('remove-item', '{{ $key }}');
                                                    ">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>

                                    {{-- COLLAPSE --}}
                                    <button class="btn-sm btn-light cart-collapse-btn" data-bs-toggle="collapse"
                                        data-bs-target="#zone-{{ $key }}">
                                        <i class="fa-solid fa-angle-down"></i>
                                    </button>

                                </div>
                            </div>

                            <div class="collapse show" id="zone-{{ $key }}">

                                <!-- ================= PLAN ITEM ================= -->
                                <div class="d-flex justify-content-between align-items-center p-3 rounded mb-2 cart-details">

                                    <div>
                                       <h5>
    @if(isset($item['is_unlimited']) && $item['is_unlimited'])
        Unlimited Data, {{ $item['days'] }} Days
    @else
        {{ $item['plan_name'] }}
    @endif
</h5>
                                        <div class="text-muted small d-flex align-items-center">
                                            <div class="me-3 d-flex align-items-center">
                                                <i class="fa-solid fa-check"></i>
                                                {{ $item['quantity'] }} × Plan
                                            </div>
                                        </div>
                                        @if(session('pending_promo') === 'NORSETEST' && empty($item['is_promo_free']))
    <div class="promo-applied-under-plan" style="border-left-color: #f0c000; background: #fffbeb;">
        <i class="fa-solid fa-bolt" style="color:#b45309;"></i>
        <span style="color:#b45309;">
            <strong>+2GB Bonus Data</strong> will be added to this eSIM on activation
        </span>
    </div>
@elseif(session('pending_promo') === 'NORSEBOGO' && empty($item['is_promo_free']))
    <div class="promo-applied-under-plan" style="border-left-color: #dc3545; background: #fff5f5;">
        <i class="fa-solid fa-gift" style="color:#dc3545;"></i>
        <span style="color:#dc3545;">
            <strong>Buy 1 Get 1 Free!</strong> — You'll receive 2 eSIMs at checkout for the price of 1
        </span>
    </div>
@endif
                                    </div>

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="fw-bold">
                                           {{ __('currency.symbol') }}{{ number_format($item['price'],2) }}
                                        </div>

                                        {{-- PLAN QUANTITY (ALWAYS EDITABLE ON CART PAGE) --}}
                                        <div class="item-count" style="width: 100px;">
                                            <button class="decre-btn"
                                                @click="$store.cart.updateQuantity('{{ $key }}', 'plan', 'dec')">
                                                -
                                            </button>

                                            <span x-text="$store.cart.items['{{ $key }}'].quantity"></span>

                                            <button class="incre-btn"
                                                @click="$store.cart.updateQuantity('{{ $key }}', 'plan', 'inc')">
                                                +
                                            </button>
                                        </div>

                                        <div class="fw-bold">
                                            {{ __('currency.symbol') }}<span x-text="$store.cart.planTotal($store.cart.items['{{ $key }}']).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= TALK TIME ADDON ================= -->
                                <div class="d-flex justify-content-between align-items-center p-3 rounded mt-4 cart-details">

                                    <div class="d-flex align-items-center gap-3" x-data="{ open: false }">
                                        <h5>Talk Time Options</h5>

                                        <!-- ADD-ONS BADGE -->
                                        <span class="addon position-relative badge bg-success" @mouseenter="open = true"
                                            @mouseleave="open = false">
                                            Add-ons

                                            <!-- POPUP -->
                                            <div x-show="open" x-transition.opacity class="addon-popover">
                                                Extra talk time can be added to this plan.
                                            </div>
                                        </span>
                                    </div>

                                   @if (!empty($item['addons']['talk_time']['enabled']))

                                        {{-- ENABLED STATE --}}
                                        <div class="d-flex align-items-center gap-3">

                                            <strong>
                                               {{ __('currency.symbol') }}{{ number_format($item['addons']['talk_time']['price'],2) }}
                                            </strong>

                                            {{-- TALK TIME QTY (ALWAYS EDITABLE ON CART PAGE) --}}
                                            <div class="item-count">
                                                <button class="decre-btn"
                                                    @click="$store.cart.updateQuantity('{{ $key }}','talk_time','dec')">
                                                    -
                                                </button>

                                                <span x-text="$store.cart.items['{{ $key }}'].addons.talk_time.qty"></span>

                                                <button class="incre-btn"
                                                    @click="$store.cart.updateQuantity('{{ $key }}','talk_time','inc')">
                                                    +
                                                </button>
                                            </div>

                                            <strong>
                                                {{ __('currency.symbol') }}<span x-text="$store.cart.addonTotal($store.cart.items['{{ $key }}'])"></span>
                                            </strong>
                                        </div>

                                    @else
                                        {{-- DISABLED STATE --}}
                                        <button class="addon-add-btn" @click="$store.cart.enableAddon('{{ $key }}','talk_time')">
                                            + Add Talk Time ({{ __('currency.symbol') }}10)
                                        </button>
                                    @endif
                                </div>

                                <!-- ================= AUTO TOPUP ================= -->
                                <div class="p-3 mt-3 rounded auto-topup d-flex justify-content-between align-items-center">

                                    <div>
                                        <strong>Auto-Topup</strong>
                                        <p class="small  mb-0">
                                            Automatically recharge when data runs out
                                        </p>
                                    </div>

                                  @if (!empty($item['addons']['auto_topup']['enabled']))

                                        <span class="badge bg-success">Enabled</span>

                                        <button class="btn btn-sm text-danger"
                                            @click="$store.cart.disableAddon('{{ $key }}','auto_topup')">
                                            Remove
                                        </button>

                                    @else
                                        <button class="addon-add-btn" @click="$store.cart.enableAddon('{{ $key }}','auto_topup')">
                                            + Add Auto-Topup
                                        </button>
                                    @endif
                                </div>

                                <!-- ================= PLAN TOTAL ================= -->
                               <div class="fw-bold text-end m-2">
    Plan Total:
    {{ __('currency.symbol') }}
    <span x-text="$store.cart.itemTotal($store.cart.items['{{ $key }}']).toFixed(2)"></span>
</div>

                            </div>
                        </div>
                    @endforeach

                @endif
            </div>

            <!-- ================= RIGHT COLUMN ================= -->
            <div class="col-lg-4">

                <div class="card Order-summary">
                    <div class="card-body">

                        <h5 class="card-title fw-bold">Order Summary</h5>
                        <p class="mb-1 text-black">{{ count($cart) }} Items</p>
                        <hr>

                        <div class="d-flex justify-content-between mb-2 sub-total">
                            <p>Subtotal <span>({{ __('currency.code') }})</span></p>
                            <h4 class="fw-bold text-primary">
                                {{ __('currency.symbol') }}<span x-text="$store.cart.subtotal.toFixed(2)"></span>
                            </h4>
                        </div>

                        <p class="text-center w-100">
                            Subtotal does not include applicable taxes
                        </p>

                        {{-- CART PAGE → GO TO CHECKOUT --}}
                        {{-- Login check YAHAN NAHI, checkout page par hoga --}}
                      <button 
                            class="ready-btn w-100 {{ count($cart) === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                            wire:click="goToCheckout"
                        >
                            I'm ready to Pay
                        </button>

                        @if($errorMessage)
                            <p class="text-danger mt-2 small">
                                {{ $errorMessage }}
                            </p>
                        @endif


                        {{-- CONSENT --}}
                        <div class="form-check mt-3 small">
                            <input class="form-check-input border-primary" type="checkbox" id="esimConsent"
                                wire:model="consentAccepted">

                            <label class="form-check-label" for="esimConsent">
                                I understand that in order to use an eSIM, I need an eSIM compatible phone
                                (<a href="{{ route('esim-compatible') }}" class="text-decoration-none">please see list here</a>).
                                I am ok with the
                                <a href="#" target="_blank">gsm2go eSIM Terms and Conditions</a>
                                and
                                <a href="#" target="_blank">Privacy Policy</a>.
                                I confirm my travel destination countries are included in the plan I selected.
                            </label>

                            @error('consentAccepted')
                                <p class="text-danger mt-1 small">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

               {{-- <div class="text-center pt-4">
                    <strong class="border-bottom border-black">
                        Satisfaction Guaranteed
                    </strong>
                    <p class="small">
                        If you're not completely happy with your purchase, contact our Guides 24/7/365,
                        and we'll make it right.
                    </p>
                </div> --}}

            </div>
        </div>
    </div>
</div>

@include('commons.plansworking')