<div class="container-fluid page-background p-1">
    <div class="container m-auto">

        <div class="mt-4 user-box">
            <h4 class="text-success">Checkout</h4>
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
                                    {{-- ── Plan row ── --}}
                                    <div class="package-item checkout-plan-row">
                                        <div class="checkout-plan-info">
                                            <strong class="checkout-plan-name">{{ $item['plan_name'] }}</strong>
                                            <div class="checkout-plan-meta">
                                                {{-- FREE badge for injected freeEsim item --}}
                                                @if(!empty($item['is_promo_free']))
                                                    <span class="badge bg-success me-1">FREE</span>
                                                @endif
                                                <span><i class="fa-solid fa-check"></i> {{ $item['plan_name'] }}</span>
                                            </div>
                                        </div>

                                        {{-- Quantity — show B1G1 doubled badge --}}
                                        <div class="checkout-plan-qty">
                                            {{ $item['quantity'] }}
                                            @if(isset($item['b1g1_qty']))
                                                <span class="badge bg-danger ms-1" style="font-size:10px;">×2</span>
                                            @endif
                                        </div>

                                        {{-- Price --}}
                                        <div class="checkout-plan-price">
                                            @if(!empty($item['is_promo_free']))
                                                <span class="text-success fw-bold">FREE</span>
                                            @elseif(($appliedPromo['type'] ?? '') === 'discount' && empty($item['is_promo_free']))
                                                {{-- Show original strikethrough + discounted price --}}
                                                @php
                                                    $originalPrice   = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                                                    $discountedPrice = round($originalPrice * (1 - ($appliedPromo['discount'] / 100)), 2);
                                                @endphp
                                                <span class="text-muted text-decoration-line-through me-1" style="font-size:12px;">
                                                    ${{ number_format($originalPrice, 2) }}
                                                </span>
                                                <span class="text-success fw-bold">
                                                    ${{ number_format($discountedPrice, 2) }}
                                                </span>
                                            @else
                                                ${{ number_format(($item['price'] ?? 0), 2) }}
                                            @endif
                                        </div>
                                    </div>

                                    {{-- ── PROMO APPLIED INFO UNDER PLAN ─────────────────────────
                                         Yahan har plan ke neeche dikhega promo ka effect
                                    ──────────────────────────────────────────────────────── --}}
                                    @if(!empty($appliedPromo) && empty($item['is_promo_free']))

                                        {{-- discount: saving amount dikhao --}}
                                        @if($appliedPromo['type'] === 'discount')
                                            @php
                                                $itemBase    = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                                                $itemSaving  = round($itemBase * ($appliedPromo['discount'] / 100), 2);
                                            @endphp
                                            <div class="promo-applied-under-plan">
                                                <i class="fa-solid fa-tag text-success me-1"></i>
                                                <span class="text-success">
                                                    Promo <strong>{{ $appliedPromo['code'] }}</strong>
                                                    — {{ $appliedPromo['discount'] }}% off
                                                    <strong>(saving ${{ number_format($itemSaving, 2) }})</strong>
                                                </span>
                                            </div>

                                        {{-- bonusData: sirf pehle item pe (is_bonus_item=true) --}}
                                        @elseif($appliedPromo['type'] === 'bonusData' && !empty($item['is_bonus_item']))
    <div class="promo-applied-under-plan bonus-highlight">
        <i class="fa-solid fa-bolt me-1"></i>
        <span>
            <strong>+{{ $appliedPromo['bonus_label'] ?? ($appliedPromo['amount'].'GB') }}</strong>
            Bonus Data will be added to this eSIM on activation
        </span>
    </div>
                                        @elseif(
                                            $appliedPromo['type'] === 'buy1get1'
                                            && (
                                                !empty($item['is_promo_free'])
                                                || isset($item['b1g1_qty'])
                                            )
                                        )
                                            <div class="promo-applied-under-plan">
                                                <i class="fa-solid fa-gift text-danger me-1"></i>
                                                <span class="text-danger">
                                                    Promo <strong>{{ $appliedPromo['code'] }}</strong>
                                                    — <strong>1 free copy</strong> of this plan included!
                                                </span>
                                            </div>
                                        @endif

                                    @endif

                                    {{-- freeEsim injected item ka label --}}
                                    @if(!empty($item['is_promo_free']))
                                        <div class="promo-applied-under-plan">
                                            <i class="fa-solid fa-circle-check text-success me-1"></i>
                                            <span class="text-success">
                                                Free eSIM via promo <strong>{{ $appliedPromo['code'] ?? '' }}</strong>
                                            </span>
                                        </div>
                                    @endif
                                    {{-- ── END PROMO INFO ── --}}

                                    {{-- Talk Time addon --}}
                                    @if(!empty($item['addons']['talk_time']['enabled']))
                                        <div class="package-item checkout-addon-row">
                                            <div class="checkout-addon-info">
                                                <strong>Talk Time Options</strong>
                                                <span class="checkout-addon-badge">Add-ons</span>
                                            </div>
                                            <div class="checkout-addon-qty">{{ $item['addons']['talk_time']['qty'] }}</div>
                                            <div class="checkout-addon-price">
                                                ${{ number_format($item['addons']['talk_time']['price'], 2) }}
                                                @if(($appliedPromo['type'] ?? '') === 'discount')
                                                    <small class="text-muted d-block" style="font-size:10px;">No discount on add-ons</small>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Auto Topup --}}
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

                {{-- ══ ORDER SUMMARY ══ --}}
                <div class="summary-box">
                    <h4 class="checkout-section-title">Order Summary</h4>
                    <p class="checkout-meta-text">{{ count($cart) }} item(s)</p>

                    {{-- Subtotal (original) --}}
                    <div class="subtotal border-top py-2">
                        <span class="checkout-summary-label">Subtotal</span>
                        <span class="checkout-summary-amount">
                            ${{ number_format(
                                collect($cart)
                                    ->filter(fn($i) => empty($i['is_promo_free']))
                                    ->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1)),
                                2
                            ) }}
                        </span>
                    </div>

                    {{-- Discount line --}}
                    @if($discountAmount > 0)
                        <div class="border-top py-2 d-flex justify-content-between align-items-center">
                            <span class="text-success small">
                                <i class="fa-solid fa-tag me-1"></i>
                                {{ $appliedPromo['code'] ?? '' }} ({{ $appliedPromo['discount'] ?? '' }}% off plans)
                            </span>
                            <span class="text-success fw-bold">-${{ number_format($discountAmount, 2) }}</span>
                        </div>
                    @endif

                    {{-- bonusData in summary --}}
                    @if(!empty($appliedPromo) && ($appliedPromo['type'] ?? '') === 'bonusData')
                        <div class="border-top py-2 d-flex justify-content-between align-items-center">
                            <span class="small" style="color:#b45309;">
                                <i class="fa-solid fa-bolt me-1"></i>
                                {{ $appliedPromo['code'] ?? '' }} — Bonus data on activation
                            </span>
                            <span class="fw-bold" style="color:#b45309;">
                                +{{ $appliedPromo['bonus_label'] ?? ($appliedPromo['amount'] ?? 0).'GB' }}
                            </span>
                        </div>
                    @endif

                    {{-- buy1get1 in summary --}}
                    @if(!empty($appliedPromo) && ($appliedPromo['type'] ?? '') === 'buy1get1')
                        <div class="border-top py-2 d-flex justify-content-between align-items-center">
                            <span class="text-danger small">
                                <i class="fa-solid fa-gift me-1"></i>
                                {{ $appliedPromo['code'] ?? '' }} — Buy 1 Get 1 Free
                            </span>
                            <span class="text-danger fw-bold">2× eSIMs</span>
                        </div>
                    @endif

                    {{-- freeEsim in summary --}}
                    @if(!empty($appliedPromo) && ($appliedPromo['type'] ?? '') === 'freeEsim')
                        <div class="border-top py-2 d-flex justify-content-between align-items-center">
                            <span class="text-success small">
                                <i class="fa-solid fa-circle-plus me-1"></i>
                                {{ $appliedPromo['code'] ?? '' }} — Free eSIM added
                            </span>
                            <span class="text-success fw-bold">FREE</span>
                        </div>
                    @endif

                    {{-- Total --}}
                    <div class="total border-top py-2">
                        <strong class="checkout-summary-label">Total (USD)</strong>
                        <strong class="checkout-summary-total">${{ number_format($grandTotal, 2) }}</strong>
                    </div>
                </div>
            </div>

            {{-- ═══════════ RIGHT SIDE ═══════════ --}}
            <div class="checkout-right page-background">

                {{-- Saved cards --}}
                @if(Auth::check() && Auth::user()->hasSavedCard())
                    <div class="saved-cards-section mb-3">
                        <h5>Saved Cards</h5>
                        @foreach(Auth::user()->savedCards as $card)
                            <div class="payment-option-row">
                                <label>
                                    <input type="radio" wire:model="selectedCardMethodId" value="{{ $card->payment_method_id }}">
                                    <img src="{{ asset($card->brand_icon) }}" height="22">
                                    {{ $card->display_label }}
                                </label>
                            </div>
                        @endforeach
                        <div class="payment-option-row">
                            <label>
                                <input type="radio" wire:model="selectedCardMethodId" value="">
                                Use new card
                            </label>
                        </div>
                    </div>
                @endif

                {{-- ══ PROMO CODE SECTION ══ --}}
                @if(!$isGuest)
                    <div class="mb-3 p-3 bg-white rounded border">
                        <h6 class="mb-2 text-muted">
                            <i class="fa-solid fa-tag me-1"></i> Promo Code
                        </h6>

                        @if(empty($appliedPromo))
                            {{-- Input state --}}
                            <div class="d-flex gap-2">
                                <input
                                    type="text"
                                    wire:model.defer="promoCode"
                                    wire:keydown.enter="applyPromo"
                                    class="form-control form-control-sm"
                                    placeholder="Enter promo code"
                                    style="text-transform:uppercase; letter-spacing:0.05em;"
                                    autocomplete="off"
                                />
                                <button
                                    wire:click="applyPromo"
                                    wire:loading.attr="disabled"
                                    wire:target="applyPromo"
                                    class="btn btn-outline-success btn-sm text-nowrap"
                                    style="min-width:70px;"
                                >
                                    <span wire:loading.remove wire:target="applyPromo">Apply</span>
                                    <span wire:loading wire:target="applyPromo">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                            </div>

                            @if($promoError)
                                <p class="text-danger small mt-1 mb-0">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    {{ $promoError }}
                                </p>
                            @endif

                        @else
                            {{-- ══ APPLIED STATE — type-specific badge ══ --}}

                            @if(($appliedPromo['type'] ?? '') === 'discount')
                                <div class="promo-applied-badge-box promo-discount">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold text-success">
                                                <i class="fa-solid fa-circle-check me-1"></i>
                                                {{ $appliedPromo['code'] }} Applied!
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge bg-success">{{ $appliedPromo['discount'] }}% OFF</span>
                                                <span class="text-success ms-2 small">
                                                    You save <strong>${{ number_format($discountAmount, 2) }}</strong> on plans
                                                </span>
                                            </div>
                                            <small class="text-muted">Add-ons charged at full price</small>
                                        </div>
                                        <button wire:click="removePromo" class="btn btn-link btn-sm text-danger p-0 ms-2">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>

                            @elseif(($appliedPromo['type'] ?? '') === 'bonusData')
                                @php
                                    $bonusLabel = ($appliedPromo['bonus_label'] ?? $appliedPromo['amount'].'GB');

                                @endphp
                                <div class="promo-applied-badge-box promo-bonus">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold" style="color:#b45309;">
                                                <i class="fa-solid fa-circle-check me-1 text-success"></i>
                                                {{ $appliedPromo['code'] }} Applied!
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge" style="background:#f0c000; color:#000;">+{{ $bonusLabel }} BONUS</span>
                                                <span class="ms-2 small" style="color:#b45309;">
                                                    Extra <strong>{{ $bonusLabel }}</strong> added on activation
                                                </span>
                                            </div>
                                            <small class="text-muted">No price change — bonus credited after eSIM activates</small>
                                        </div>
                                        <button wire:click="removePromo" class="btn btn-link btn-sm text-danger p-0 ms-2">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>

                            @elseif(($appliedPromo['type'] ?? '') === 'freeEsim')
                                <div class="promo-applied-badge-box promo-free">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold text-success">
                                                <i class="fa-solid fa-circle-check me-1"></i>
                                                {{ $appliedPromo['code'] }} Applied!
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge bg-success">FREE eSIM</span>
                                                <span class="text-success ms-2 small">
                                                    <strong>{{ $appliedPromo['gb'] }}GB eSIM</strong> added to your order
                                                </span>
                                            </div>
                                            <small class="text-muted">Free eSIM shown in cart above</small>
                                        </div>
                                        <button wire:click="removePromo" class="btn btn-link btn-sm text-danger p-0 ms-2">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>

                            @elseif(($appliedPromo['type'] ?? '') === 'buy1get1')
                                <div class="promo-applied-badge-box promo-b1g1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold text-danger">
                                                <i class="fa-solid fa-circle-check me-1 text-success"></i>
                                                {{ $appliedPromo['code'] }} Applied!
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge bg-danger">BUY 1 GET 1</span>
                                                <span class="text-danger ms-2 small">
                                                    <strong>Double eSIMs</strong> — pay for 1, get 2
                                                </span>
                                            </div>
                                            <small class="text-muted">Add-ons apply to paid eSIM only</small>
                                        </div>
                                        <button wire:click="removePromo" class="btn btn-link btn-sm text-danger p-0 ms-2">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif

                        @endif
                    </div>
                @endif
                {{-- ══ END PROMO CODE ══ --}}

                {{-- Payment method selector --}}
                <div id="payment-method-selector" class="{{ $isGuest ? 'opacity-50 pointer-events-none' : '' }}">
                    <div class="payment-option-row payment-option-selected" onclick="selectPayMethod('card')" id="method-card">
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" value="card" checked onchange="selectPayMethod('card')" />
                            Credit / Debit Card
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/visa.png') }}" alt="Visa" />
                            <img src="{{ asset('images/mastercard.png') }}" alt="Mastercard" />
                            <img src="{{ asset('images/amex.png') }}" alt="Amex" />
                        </div>
                    </div>
                    <div class="payment-option-row" onclick="selectPayMethod('applepay')" id="method-applepay">
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" value="applepay" onchange="selectPayMethod('applepay')" />
                            Apple Pay
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/applepay.png') }}" alt="Apple Pay" />
                        </div>
                    </div>
                    <div class="payment-option-row" onclick="selectPayMethod('googlepay')" id="method-googlepay">
                        <label class="checkout-payment-label">
                            <input type="radio" name="pay" value="googlepay" onchange="selectPayMethod('googlepay')" />
                            Google Pay
                        </label>
                        <div class="pay-logo">
                            <img src="{{ asset('images/gpay.png') }}" alt="Google Pay" />
                        </div>
                    </div>
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

                {{-- Airwallex drop-in --}}
                <div id="airwallex-element-wrapper" style="display:none;" class="mt-3 p-3 border rounded bg-white">
                    <p class="small text-muted mb-2">
                        <i class="fa-solid fa-lock text-success me-1"></i>
                        Secure payment powered by Airwallex
                    </p>
                    <div id="airwallex-dropin" style="min-height:120px;"></div>
                    <div id="airwallex-error" class="text-danger small mt-2" style="display:none;"></div>
                </div>

                {{-- Pay Now / Guest --}}
                <div class="pay-now p-4 bg-white">
                    @if(!$isGuest)
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
                        <div class="auth-footer mt-4 text-center">
                            <p class="mb-2">Don't have an account?</p>
                            <a href="{{ route('sign-up') }}" class="btn btn-outline-success  w-100">Create an Account</a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ══ PROMO BADGE STYLES ══ --}}
<style>
.promo-applied-under-plan {
    font-size: 13px;
    padding: 6px 12px 6px 12px;
    margin: 4px 0 8px 0;
    border-left: 3px solid #198754;
    background: #f0faf4;
    border-radius: 0 6px 6px 0;
}

.promo-applied-badge-box {
    padding: 10px 12px;
    border-radius: 8px;
    margin-top: 4px;
}

.promo-discount {
    background: #f0faf4;
    border: 1px solid #a3d9b1;
}

.promo-bonus {
    background: #fffbeb;
    border: 1px solid #f0c000;
}

.promo-free {
    background: #f0faf4;
    border: 1px solid #a3d9b1;
}

.promo-b1g1 {
    background: #fff5f5;
    border: 1px solid #f5a0a0;
}
</style>