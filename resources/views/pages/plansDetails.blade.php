<section class="plan-details" x-data="{ selectedPlan: {{ $selectedPlanId }} }" x-init="
        // plan price
        // initial sync ONLY ONCE
        $store.cart.setPlanPrice({{ optional($plans->first())->USD ?? 0 }});

        // sync quantity (primitive)
        $watch('$store.cart.qty', value => $wire.set('quantity', value));

        // sync auto topup
        $watch('$store.cart.addons.auto_topup.enabled', value =>
            $wire.set('addons.auto_topup.enabled', value)
        );

        // sync talk time enabled
        $watch('$store.cart.addons.talk_time.enabled', value =>
            $wire.set('addons.talk_time.enabled', value)
        );

        // sync talk time qty
        $watch('$store.cart.addons.talk_time.qty', value =>
            $wire.set('addons.talk_time.qty', value)
        );">
    <div class="container">

        {{-- ================= ZONE TITLE ================= --}}
        <h3>{{ $zone->name }}</h3>

        <div class="row gy-4">

            <!-- ================= LEFT COLUMN ================= -->
            <div class="col-lg-6">

                {{-- Zone Image --}}
                <img src="{{ asset('images/continent/' . $zone->image) }}" alt="{{ $zone->name }} eSIM"
                    class="img-fluid rounded mb-3 plan-continent" />

                {{-- Countries Covered --}}
                <div class="mb-3 d-flex align-items-center justify-content-between border rounded p-3">
                    <div>
                        <i class="fa-solid fa-globe me-2"></i>
                        {{ count(explode(',', $zone->countries ?? '')) }}+ countries covered
                    </div>
                    <button class="btn-sm btn-light cart-collapse-btn" data-bs-toggle="collapse"
                        data-bs-target="#countries">
                        <i class="fa-solid fa-angle-down"></i>
                    </button>
                </div>

                {{-- Countries List --}}
                <div class="talk-time-content collapse mb-3 show" id="countries">
                    <div class="row countries-list">

                        @foreach ($countries as $country)
                            <div class="col-md-2 col-3  text-center mb-2">

                                <img src="{{ asset('images/country_flag/' . $country->flag) }}"
                                    alt="{{ $country->country_name }}" class="img-fluid mb-1" style="max-width:40px">

                                <label>{{ $country->country_name }}</label>
                            </div>
                        @endforeach

                    </div>
                </div>

                {{-- About --}}
                <!-- <h5>About This Plan</h5>
                <p>{{ $zone->description ?? 'Enjoy fast and reliable eSIM connectivity worldwide.' }}</p>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore
                    et
                    dolore magna aliqua. </p>
                <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                    consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat
                    nulla
                    pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit
                    anim
                    id est laborum.</p>

                {{-- Features --}}
                <div class="features">
                    <h5>Features:</h5>
                    <ul class="feature-list">
                        <li> Duis aute irure dolor in reprehenderit</li>
                        <li> Voluptate velit esse cillum dolore</li>
                        <li> Fugiat nulla pariatur. Excepteur sint occaecat</li>
                        <li> Cupidatat non proident, sunt in culpa qui</li>
                        <li> Officia deserunt mollit anim id est laborum.</li>
                    </ul>
                </div> -->

            </div>

            <!-- ================= RIGHT COLUMN ================= -->
            <div class="col-lg-6">

                <h4 class="plans-package-title">
                    <i class="fa-solid fa-globe"></i>
                    eSIM for {{ $zone->name }}
                </h4>

                <p class="text-muted">
                    Get an eSIM card for {{ $zone->name }} and enjoy reliable and affordable internet access.
                </p>

                <h6>Select your eSim Package</h6>
                {{-- ================= PLANS LOOP ================= --}}
                @forelse ($plans as $index => $plan)

                    <div class="package-option" :class="{ 'active': selectedPlan === {{ $plan->id }} }" @click="
                                                                    selectedPlan = {{ $plan->id }};
                                                                    $store.cart.setPlanPrice({{ $plan->USD }});
                                                                    $wire.set('selectedPlanId', {{ $plan->id }}, false);
                                                                ">

                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>{{ $plan->Days }} Days Pack</h5>

                                <div class="text-muted small d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fa-solid fa-check"></i> {{ $plan->GB }} GB
                                    </div>
                                    <div>
                                        <i class="fa-solid fa-check"></i> {{ $plan->Days }} Days
                                    </div>
                                </div>
                            </div>

                            <strong class="price">${{ $plan->USD }}</strong>
                        </div>
                    </div>
                @empty
                    <p>No plans available for this destination.</p>
                @endforelse
                {{-- Quantity --}}
                <div class="d-flex my-3 align-items-center">
                    <label class="form-label me-3">Quantity:</label>

                    <div class="item-count">

                        <button class="decre-btn" @click="$store.cart.qty--">-</button>
                        <span class="count" x-text="$store.cart.qty"></span>

                        <button class="incre-btn" @click="$store.cart.qty++">+</button>
                    </div>
                </div>


                <!-- Add-ons -->
                <div class="mt-4">
                    <h6>Recommended Add-ons</h6>
                    {{-- ================= AUTO TOPUP ADDON ================= --}}
                    <div class="addon-box border bg-white mb-3" x-data="{ loading: false }">

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center">

                            <div class="d-flex align-items-center">
                                <input type="checkbox" id="autoTopupAddon" class="me-2" data-bs-toggle="modal"
                                    data-bs-target="#autoTopupModal" autocomplete="off" :disabled="loading">


                                <label for="autoTopupAddon" class="fw-bold mb-0">
                                    Auto-topup
                                </label>
                            </div>

                            <button class="btn btn-sm  bg-none" data-bs-toggle="collapse"
                                data-bs-target="#autoTopupContent">
                                <i class="fa-solid fa-angle-down"></i>
                            </button>
                        </div>

                        <!-- Collapsible Content -->
                        <div class="collapse show" id="autoTopupContent">
                            <div class="p-3 border-top small text-muted">
                                <p class="mb-2">
                                    Never run out of data.
                                    When your data allocation reaches its limit,
                                    we will automatically change your account
                                    and apply your plan again for you.
                                </p>
                            </div>
                        </div>


                        <div class="modal fade" id="autoTopupModal" tabindex="-1" aria-hidden="true"
                            data-bs-backdrop="static" data-bs-keyboard="false">

                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content auto-topup-modal">
                                    <div class="modal-body text-center p-4">

                                        <div class="info-icon mb-3">
                                            <i class="fa-solid fa-info"></i>
                                        </div>

                                        <p>
                                            When auto-topup is selected, we will apply a charge to the same
                                            credit card once your data runs out.
                                        </p>

                                        <p class="mb-4">
                                            Auto-topup requires payment via credit card.
                                        </p>

                                        <!-- OK BUTTON -->
                                        <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal"
                                            @click="loading = true;
                                                $store.cart.addons.auto_topup.enabled =
                                                    !$store.cart.addons.auto_topup.enabled;
                                                // 🔄 simulate async / wait for Livewire sync
                                                setTimeout(() => { loading = false;}, 700);
                                            ">
                                            OK
                                        </button>

                                    </div>
                                </div>
                            </div>

                        </div>
                        </div>
                        {{-- Talk Time Addon --}}
                        <div class="addon-box border p-3 bg-white">
                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <div class="">
                                    <!-- <input type="checkbox" id="talkAddon" checked data-price="10" > -->
                                    <input type="checkbox" x-model="$store.cart.addons.talk_time.enabled">
                                    <label class="form-check-label">
                                        <strong>Talk Time Options</strong>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-3"> <strong> $10</strong>
                                    <div class="ms-3 item-count addon-qty">

                                        <div class="item-count addon-qty">

                                            <button class="decre-btn" @click="
                                                if ($store.cart.addons.talk_time.qty > 1)
                                                    $store.cart.addons.talk_time.qty--
                                            ">
                                                -
                                            </button>
                                            <span class="count" x-text="$store.cart.addons.talk_time.qty"></span>

                                            <button class="incre-btn" @click="$store.cart.addons.talk_time.qty++">
                                                +
                                            </button>
                                        </div>


                                    </div>

                                    <button class=" btn-sm btn-light cart-collapse-btn" data-bs-toggle="collapse"
                                        data-bs-target="#talktime-content"><i
                                            class="fa-solid fa-angle-down"></i></button>
                                </div>
                            </div>
                            <div class="talk-time-content ps-3 collapse show" id="talktime-content">
                                <div class="talk-time-list"> <label>Get 100 Voice Minutes and 50 SMS texts</label>
                                    <ul class="mb-2 small">
                                        <li>50 outgoing minutes</li>
                                        <li> 50 incoming minutes</li>
                                        <li>50 outgoing texts</li>
                                        <li>unlimited incoming texts</li>
                                    </ul>
                                </div>
                                <p><a href="#" class="text-decoration-underline small">Click here</a> to see dial-to
                                    countries
                                </p>
                            </div>
                        </div>
                    </div>
                    {{-- Continue --}}
                    <div class="mt-4 border-top">

                        <button class="pay-now-btn w-100 py-2 d-flex justify-content-center align-items-center gap-2"
                            @click="$wire.call('continue', $store.cart.total)" wire:loading.attr="disabled"
                            wire:target="continue">
                            {{-- Normal state --}}
                            <span wire:loading.remove wire:target="continue">
                                Continue ($<span x-text="$store.cart.total"></span>)
                            </span>

                            {{-- Loading state --}}
                            <span wire:loading wire:target="continue">
                                <span class="spinner-border spinner-border-sm"></span>
                                Processing...
                            </span>
                        </button>

                        <p class="text-center text-muted small mt-1">
                            You won't be charged yet
                        </p>

                    </div>

                </div>
            </div>
        </div>
</section>