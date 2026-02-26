<div class="container-fluid page-background p-1">
    <div class="container m-auto">

        <div class="mt-4 user-box">
            <h4 class="text-success">checkout</h4>
        </div>

        <div class="container checkout-container">

            {{-- ═══════════ LEFT SIDE ═══════════ --}}
            <div class="checkout-left">
                <div class="checkout-items page-background">
                    @foreach($groupedCart as $zoneItems)
                        <div class="package-box mb-4">
                            <div class="head-line">
                                <h3 class="checkout-zone-title">{{ $zoneItems[0]['zone_name'] ?? 'Zone' }}</h3>
                                <a href="{{ route('cart') }}"><i class="fa-solid fa-pen-to-square"></i></a>
                            </div>
                            @foreach($zoneItems as $item)
                                <div>
                                    <div class="package-item checkout-plan-row">
                                        <div class="checkout-plan-info">
                                            <strong class="checkout-plan-name">{{ $item['plan_name'] }}</strong>
                                            <div class="checkout-plan-meta">
                                                <span><i class="fa-solid fa-check"></i> {{ $item['plan_name'] }}</span>
                                            </div>
                                        </div>
                                        <div class="checkout-plan-qty">{{ $item['quantity'] }}</div>
                                        <div class="checkout-plan-price">${{ number_format($item['price'], 2) }}</div>
                                    </div>
                                    @if(!empty($item['addons']['talk_time']['enabled']))
                                        <div class="package-item checkout-addon-row">
                                            <div class="checkout-addon-info">
                                                <strong>Talk Time Options</strong>
                                                <span class="checkout-addon-badge">Add-ons</span>
                                            </div>
                                            <div class="checkout-addon-qty">{{ $item['addons']['talk_time']['qty'] }}</div>
                                            <div class="checkout-addon-price">${{ number_format($item['addons']['talk_time']['price'], 2) }}</div>
                                        </div>
                                    @endif
                                    <div class="checkout-auto-topup">
                                        <h4 class="head-line">AUTO-TOPUP</h4>
                                        <div class="auto-topup-row d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $item['plan_name'] }}</strong>
                                                <p class="small text-muted mb-0">Automatic recharge when data runs out</p>
                                            </div>
                                            @if(data_get($item, 'addons.auto_topup.enabled'))
                                                <span class="badge bg-success">Enabled</span>
                                            @else
                                                <span class="badge bg-secondary">Not enabled</span>
                                            @endif
                                        </div>
                                        <p class="small text-muted">Auto-topup preferences can be changed from the cart.</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="summary-box">
                    <h4 class="checkout-section-title">Order Summary</h4>
                    <p class="checkout-meta-text">{{ count($cart) }} item(s)</p>
                    <div class="subtotal border-top py-2">
                        <span class="checkout-summary-label">Subtotal</span>
                        <span class="checkout-summary-amount">${{ number_format($grandTotal, 2) }}</span>
                    </div>
                    <div class="total border-top py-2">
                        <strong class="checkout-summary-label">Subtotal (USD)</strong>
                        <strong class="checkout-summary-total">${{ number_format($grandTotal, 2) }}</strong>
                    </div>
                </div>
            </div>

            {{-- ═══════════ RIGHT SIDE ═══════════ --}}
            <div class="checkout-right page-background">

                {{-- ══ PAYMENT METHOD SELECTOR ══ --}}
                <div id="payment-method-selector" class="{{ $isGuest ? 'opacity-50 pointer-events-none' : '' }}">

                    {{-- Card --}}
                    <div class="payment-option-row payment-option-selected" onclick="selectPayMethod('card')" id="method-card">
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" value="card" checked onchange="selectPayMethod('card')" />
                            Credit / Debit Card
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/visa.png') }}"       alt="Visa" />
                            <img src="{{ asset('images/mastercard.png') }}" alt="Mastercard" />
                            <img src="{{ asset('images/amex.png') }}"       alt="Amex" />
                        </div>
                    </div>

                    {{-- Apple Pay --}}
                    <div class="payment-option-row" onclick="selectPayMethod('applepay')" id="method-applepay">
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" value="applepay" onchange="selectPayMethod('applepay')" />
                            Apple Pay
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/applepay.png') }}" alt="Apple Pay" />
                        </div>
                    </div>

                    {{-- Google Pay --}}
                    <div class="payment-option-row" onclick="selectPayMethod('googlepay')" id="method-googlepay">
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" value="googlepay" onchange="selectPayMethod('googlepay')" />
                            Google Pay
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/gpay.png') }}" alt="Google Pay" />
                        </div>
                    </div>

                    {{-- PayPal --}}
                    <div class="payment-option-row" onclick="selectPayMethod('paypal')" id="method-paypal">
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" value="paypal" onchange="selectPayMethod('paypal')" />
                            PayPal
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/paypal.png') }}" alt="PayPal" />
                        </div>
                    </div>
                </div>

                {{-- ══ AIRWALLEX ELEMENT WRAPPER ══ --}}
                <div id="airwallex-element-wrapper" style="display:none;" class="mt-3 p-3 border rounded bg-white">
                    <p class="small text-muted mb-2">
                        <i class="fa-solid fa-lock text-success me-1"></i>
                        Secure payment powered by Airwallex
                    </p>

                    {{-- Card element --}}
                    <div id="airwallex-card"      style="display:none; min-height:60px;"></div>
                    {{-- Apple Pay button --}}
                    <div id="airwallex-applepay"  style="display:none; min-height:50px;"></div>
                    {{-- Google Pay button --}}
                    <div id="airwallex-googlepay" style="display:none; min-height:50px;"></div>
                    {{-- PayPal button --}}
                    <div id="airwallex-paypal"    style="display:none; min-height:50px;"></div>

                    <div id="airwallex-error" class="text-danger small mt-2" style="display:none;"></div>

                    {{-- Confirm button — sirf card ke liye --}}
                    <button id="airwallex-pay-btn"
                            class="pay-now-btn w-100 mt-3"
                            onclick="confirmAirwallexPayment()"
                            disabled
                            style="display:none;">
                        <i class="fa-solid fa-lock me-1"></i>
                        Confirm & Pay ${{ number_format($grandTotal, 2) }}
                    </button>
                </div>

                {{-- ══ PAY NOW / GUEST SECTION ══ --}}
                <div class="pay-now p-4 bg-white">
                    @if(! $isGuest)
                        <button
                            id="pay-now-init-btn"
                            wire:click="payNow"
                            wire:loading.attr="disabled"
                            class="pay-now-btn w-100"
                            {{ count($cart) === 0 ? 'disabled' : '' }}
                        >
                            <span wire:loading.remove wire:target="payNow">
                                <i class="fa-solid fa-lock me-1"></i>
                                Pay Now (${{ number_format($grandTotal, 2) }})
                            </span>
                            <span wire:loading wire:target="payNow">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Initializing…
                            </span>
                        </button>
                        <p id="card-entry-hint" class="text-center small text-muted mt-2" style="display:none;">
                            Complete your payment above.
                        </p>
                    @else
                        <button class="pay-now-btn w-100" disabled>
                            Pay Now (${{ number_format($grandTotal, 2) }})
                        </button>
                        <div class="mt-3 text-center">
                            <p class="small text-danger mb-2">Please login to complete payment</p>
                            <button wire:click="redirectToLogin" class="btn btn-outline-dark w-100 mb-2">
                                <i class="fa-solid fa-envelope me-1"></i> Login with Email
                            </button>
                            <button wire:click="redirectToGoogle" class="btn btn-outline-danger w-100 mb-2">
                                <i class="fa-brands fa-google me-1"></i> Continue with Google
                            </button>
                            <button wire:click="redirectToApple" class="btn btn-outline-secondary w-100">
                                <i class="fa-brands fa-apple me-1"></i> Continue with Apple
                            </button>
                        </div>
                    @endif

                    <p class="checkout-terms-text mt-3">
                        By clicking to pay now, you agreed with all our
                        <strong class="d-block">Terms and Conditions</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script src="https://static.airwallex.com/components/sdk/v1/index.js"></script>
<script src="{{ asset('js/checkout-airwallex.js') }}"></script>
@endpush