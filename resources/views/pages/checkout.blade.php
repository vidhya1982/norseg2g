<div class="container-fluid page-background p-1">

    <div class="container m-auto">

        {{-- USER BOX --}}
        <div class="mt-4 user-box">
            <h4 class="text-success">
                checkout
            </h4>
        </div>

        <div class="container checkout-container">

            {{-- ================= LEFT SIDE ================= --}}
            <div class="checkout-left">

                <div class="checkout-items page-background">

                    @foreach($groupedCart as $zoneItems)

                        <div class="package-box mb-4">

                            {{-- ZONE HEADER --}}
                            <div class="head-line">
                                <h3 class="checkout-zone-title">
                                    {{ $zoneItems[0]['zone_name'] }}
                                </h3>

                                <a href="{{ route('cart') }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </div>

                            {{-- ================= PLANS + ADDONS ================= --}}
                            @foreach($zoneItems as $item)

                                <div>
                                    {{-- PLAN --}}
                                    <div class="package-item checkout-plan-row">
                                        <div class="checkout-plan-info">
                                            <strong class="checkout-plan-name">
                                                {{ $item['plan_name'] }}
                                            </strong>

                                            <div class="checkout-plan-meta">
                                                <span>
                                                    <i class="fa-solid fa-check"></i>
                                                    {{ $item['plan_name'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="checkout-plan-qty">
                                            {{ $item['quantity'] }}
                                        </div>

                                        <div class="checkout-plan-price">
                                            ${{ number_format($item['price'], 2) }}
                                        </div>
                                    </div>

                                    {{-- TALK TIME ADDON --}}
                                    @if(!empty($item['addons']['talk_time']['enabled']))
                                        <div class="package-item checkout-addon-row">
                                            <div class="checkout-addon-info">
                                                <strong>Talk Time Options</strong>
                                                <span class="checkout-addon-badge">Add-ons</span>
                                            </div>

                                            <div class="checkout-addon-qty">
                                                {{ $item['addons']['talk_time']['qty'] }}
                                            </div>

                                            <div class="checkout-addon-price">
                                                ${{ number_format($item['addons']['talk_time']['price'], 2) }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="checkout-auto-topup">
                                        <h4 class="head-line">AUTO-TOPUP</h4>



                                        <div class="auto-topup-row d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $item['plan_name'] }}</strong>
                                                <p class="small text-muted mb-0">
                                                    Automatic recharge when data runs out
                                                </p>
                                            </div>

                                            @if(data_get($item, 'addons.auto_topup.enabled'))
                                                <span class="badge bg-success">Enabled</span>
                                            @else
                                                <span class="badge bg-secondary">Not enabled</span>
                                            @endif
                                        </div>



                                        <p class="small text-muted ">
                                            Auto-topup preferences can be changed from the cart.
                                        </p>
                                    </div>

                                </div>
                            @endforeach

                            {{-- ================= AUTO TOPUP (ZONE LEVEL SUMMARY) ================= --}}


                        </div>

                    @endforeach
                </div>


                {{-- ================= SUMMARY ================= --}}
                <div class="summary-box">
                    <h4 class="checkout-section-title">Order Summary</h4>

                    <p class="checkout-meta-text">
                        {{ count($cart) }} item(s)
                    </p>

                    <div class="subtotal border-top py-2">
                        <span class="checkout-summary-label">Subtotal</span>
                        <span class="checkout-summary-amount">
                            ${{ number_format($grandTotal, 2) }}
                        </span>
                    </div>

                    <div class="total border-top py-2">
                        <strong class="checkout-summary-label">
                            Subtotal (USD)
                        </strong>
                        <strong class="checkout-summary-total">
                            ${{ number_format($grandTotal, 2) }}
                        </strong>
                    </div>
                </div>
            </div>

            {{-- ================= RIGHT SIDE ================= --}}
            <div class="checkout-right page-background">

                {{-- 🔴 PAYMENT OPTIONS (DISABLED FOR GUEST) --}}
                <div class="payment-options {{ $isGuest ? 'opacity-50 pointer-events-none' : '' }}">

                    <div>
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" checked />
                            Credit/Debit Card
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/visa.png') }}" />
                            <img src="{{ asset('images/mastercard.png') }}" />
                            <img src="{{ asset('images/amex.png') }}" />
                            <img src="{{ asset('images/applepay.png') }}" />
                        </div>
                    </div>

                    <div>
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" />
                            PayPal
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/paypal.png') }}" />
                        </div>
                    </div>

                    <div>
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" />
                            G Pay
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/gpay.png') }}" />
                        </div>
                    </div>

                    <div>
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" />
                            Apple Pay
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/applepay.png') }}" />
                        </div>
                    </div>

                </div>

                {{-- ================= PAY NOW ================= --}}
                <div class="pay-now p-4 bg-white">

                    {{-- 🔴 PAY BUTTON DISABLED FOR GUEST --}}
                    <button class="pay-now-btn" {{ $isGuest ? 'disabled' : '' }}>
                        <span class="checkout-pay-amount">
                            Pay Now (${{ number_format($grandTotal, 2) }})
                        </span>
                    </button>

                    {{-- 🔴 LOGIN OPTIONS FOR GUEST --}}
                    @if($isGuest)
                        <div class="mt-3 text-center">
                            <p class="small text-danger mb-2">
                                Please login to complete payment
                            </p>

                            <button wire:click="redirectToLogin" class="btn btn-outline-dark w-100 mb-2">
                                Login with Email
                            </button>


                            <button wire:click="redirectToGoogle" class="btn btn-outline-danger w-100 mb-2">
                                Continue with Google
                            </button>

                            <button wire:click="redirectToApple" class="btn btn-outline-secondary w-100">
                                Continue with Apple
                            </button>

                        </div>
                    @endif

                    <p class="checkout-terms-text">
                        By clicking to pay now, you agreed with all our
                        <strong class="d-block">Terms and Conditions</strong>
                    </p>
                </div>

            </div>

        </div>
    </div>
</div>