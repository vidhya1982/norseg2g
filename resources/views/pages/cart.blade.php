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
                                <h5 class="mb-0">{{ $item['zone_name'] }}</h5>

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
                                        <h5>{{ $item['plan_name'] }}</h5>

                                        <div class="text-muted small d-flex align-items-center">
                                            <div class="me-3 d-flex align-items-center">
                                                <i class="fa-solid fa-check"></i>
                                                {{ $item['quantity'] }} × Plan
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="fw-bold">
                                            ${{ $item['price'] }}
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
                                            $<span x-text="$store.cart.planTotal($store.cart.items['{{ $key }}'])"></span>
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

                                    @if ($item['addons']['talk_time']['enabled'])

                                        {{-- ENABLED STATE --}}
                                        <div class="d-flex align-items-center gap-3">

                                            <strong>
                                                ${{ $item['addons']['talk_time']['price'] }}
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
                                                $<span x-text="$store.cart.addonTotal($store.cart.items['{{ $key }}'])"></span>
                                            </strong>
                                        </div>

                                    @else
                                        {{-- DISABLED STATE --}}
                                        <button class="addon-add-btn" @click="$store.cart.enableAddon('{{ $key }}','talk_time')">
                                            + Add Talk Time ($10)
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

                                    @if ($item['addons']['auto_topup']['enabled'])

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
                                    $<span x-text="$store.cart.itemTotal($store.cart.items['{{ $key }}'])"></span>
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
                            <p>Subtotal <span>(USD)</span></p>
                            <h4 class="fw-bold text-primary">
                                $<span x-text="$store.cart.subtotal"></span>
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
                            <input class="form-check-input" type="checkbox" id="esimConsent"
                                wire:model="consentAccepted">

                            <label class="form-check-label" for="esimConsent">
                                I understand that in order to use an eSIM, I need an eSIM compatible phone
                                (<a href="#" target="_blank">please see list here</a>).
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

                <div class="text-center pt-4">
                    <strong class="border-bottom border-black">
                        Satisfaction Guaranteed
                    </strong>
                    <p class="small">
                        If you're not completely happy with your purchase, contact our Guides 24/7/365,
                        and we'll make it right.
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

@include('commons.plansworking')