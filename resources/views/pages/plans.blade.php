{{-- Banner --}}
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

{{-- Unlimited Plans — blur sirf bonusData promo pe --}}
<section id="topPlans" class="{{ $hasBonusPromo ? 'promo-active-section' : '' }}">
   
    
    <div class="container top-plans">

        <h2 class="title-header">{{ __('home.unlimited_plans') }}</h2>

        <div class="row justify-content-center mt-3">

            @forelse ($unlimitedPlans as $plan)
                <div class="col-12 col-md-6 col-lg-4">

                  <a href="{{ $hasBonusPromo ? '#' : route('plans-details', ['zone' => 1, 'type' => 'unlimited', 'days' => $plan->Days]) }}"
       class="planCard {{ $hasBonusPromo ? 'promo-disabled' : '' }}">

                        <div class="plan-card">

                            <img src="{{ asset('images/continent/' . ($dayImages[$plan->Days] ?? 'europe.png')) }}">

                            <div class="card-overlay">
                                <button class="explore-btn">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>

                           <div class="plan-label">
    <img src="{{ asset('images/' . $zone->zone_flag) }}">
    <span>{{ $plan->Days }} Days Plan</span>
    <span class="plan-price">{{ __('currency.symbol') }}{{ number_format($plan->USD,2) }}</span>
</div>

                            <div class="spec-link" data-bs-toggle="modal" data-bs-target="#specsModal"
                                onclick="event.preventDefault(); event.stopPropagation();">
                                <i class="fa-solid fa-info"></i>
                            </div>

                        </div>
                    </a>

                </div>

                <div class="modal fade" id="specsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content specs-modal">
                            <div class="modal-header specs-header">
                                <h4 class="modal-title">Europe eSIM Specifications</h4>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body specs-body">
                                <div class="spec-row"><div class="spec-title">Format</div><div class="spec-value">eSIM (eUICC Profile)</div></div>
                                <div class="spec-row"><div class="spec-title">Compatibility</div><div class="spec-value link">See Compatibility List</div></div>
                                <div class="spec-row"><div class="spec-title">Installation</div><div class="spec-value">QR Code<br>Manual Activation Code<br>Automatic (iOS 17+)</div></div>
                                <div class="spec-row"><div class="spec-title">Multiple Devices</div><div class="spec-value">No - eSIM Locked to First IMEI</div></div>
                                <div class="spec-row"><div class="spec-title">Data Activation</div><div class="spec-value">Activation Date Selected on Checkout</div></div>
                                <div class="spec-row"><div class="spec-title">Data Speed</div><div class="spec-value">5G and 4G LTE (Where available)</div></div>
                                <div class="spec-row"><div class="spec-title">Available Networks</div><div class="spec-value">Various (See Individual Country Plans)</div></div>
                                <div class="spec-row"><div class="spec-title">Max Validity</div><div class="spec-value">5 Days - 180 Days</div></div>
                                <div class="spec-row"><div class="spec-title">Wi-Fi Hotspot</div><div class="spec-value">Available on all eSIM Plans<br>No restrictions</div></div>
                                <div class="spec-row"><div class="spec-title">Voice / SMS</div><div class="spec-value">Available with Wi-Fi Calling</div></div>
                                <div class="spec-row"><div class="spec-title">APN</div><div class="spec-value">Automatic</div></div>
                                <div class="spec-row"><div class="spec-title">Usage Policy</div><div class="spec-value">Personal cellular use only.<br>Use as fixed line or router is prohibited.<br><span class="link">View AUP ›</span></div></div>
                                <div class="spec-row"><div class="spec-title">Lost Phone Policy</div><div class="spec-value">{{ __('currency.symbol') }}5 eSIM Replacement Charge</div></div>
                                <div class="spec-row"><div class="spec-title">Technical Support</div><div class="spec-value"><span>24/7/365 Helpdesk</span><br><span>24/7/365 cs@gsm2go.com</span></div></div>
                            </div>
                            <div class="modal-footer specs-footer">
                                <button class="close-modal-btn" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

            @empty
                <p>No unlimited plans available</p>
            @endforelse

        </div>
    </div>
</section>

{{-- ══ BUDGET PLANS ══ --}}
<section id="topPlans" class="{{ $hasBonusPromo ? 'promo-active-section' : '' }}">
    <div class="container top-plans">

        <h2 class="title-header">{{ __('home.Budget') }}</h2>

        <div class="row justify-content-center mt-3">

            @forelse ($budgetPlans as $plan)
                <div class="col-12 col-md-6 col-lg-4">

                    <a href="{{ route('plans-details', ['zone' => 1, 'gb' => $plan->GB]) }}"
                       class="planCard">

                        <div class="plan-card">

                            <img src="{{ asset('images/continent/' . ($imageMap[$plan->GB] ?? 'europe.png')) }}">

                            <div class="card-overlay">
                                <button class="explore-btn">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>

                           
                           <div class="plan-label">
    <img src="{{ asset('images/' . $zone->zone_flag) }}">
    <span>{{ $plan->GB }} GB Plan</span>
    <span class="plan-price">{{ __('currency.symbol') }}{{ number_format($plan->USD,2) }}</span>

    @if($hasBOGOPromo)
        <span class="bogo-badge">2×FREE</span>
    @endif
</div>

                            <div class="spec-link" data-bs-toggle="modal" data-bs-target="#specsModal"
                                onclick="event.preventDefault(); event.stopPropagation();">
                                <i class="fa-solid fa-info"></i>
                            </div>

                        </div>
                    </a>

                </div>

                <div class="modal fade" id="specsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content specs-modal">
                            <div class="modal-header specs-header">
                                <h4 class="modal-title">Europe eSIM Specifications</h4>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body specs-body">
                                <div class="spec-row"><div class="spec-title">Format</div><div class="spec-value">eSIM (eUICC Profile)</div></div>
                                <div class="spec-row"><div class="spec-title">Compatibility</div><div class="spec-value link">See Compatibility List</div></div>
                                <div class="spec-row"><div class="spec-title">Installation</div><div class="spec-value">QR Code<br>Manual Activation Code<br>Automatic (iOS 17+)</div></div>
                                <div class="spec-row"><div class="spec-title">Multiple Devices</div><div class="spec-value">No - eSIM Locked to First IMEI</div></div>
                                <div class="spec-row"><div class="spec-title">Data Activation</div><div class="spec-value">Activation Date Selected on Checkout</div></div>
                                <div class="spec-row"><div class="spec-title">Data Speed</div><div class="spec-value">5G and 4G LTE (Where available)</div></div>
                                <div class="spec-row"><div class="spec-title">Available Networks</div><div class="spec-value">Various (See Individual Country Plans)</div></div>
                                <div class="spec-row"><div class="spec-title">Max Validity</div><div class="spec-value">5 Days - 180 Days</div></div>
                                <div class="spec-row"><div class="spec-title">Wi-Fi Hotspot</div><div class="spec-value">Available on all eSIM Plans<br>No restrictions</div></div>
                                <div class="spec-row"><div class="spec-title">Voice / SMS</div><div class="spec-value">Available with Wi-Fi Calling</div></div>
                                <div class="spec-row"><div class="spec-title">APN</div><div class="spec-value">Automatic</div></div>
                                <div class="spec-row"><div class="spec-title">Usage Policy</div><div class="spec-value">Personal cellular use only.<br>Use as fixed line or router is prohibited.<br><span class="link">View AUP ›</span></div></div>
                                <div class="spec-row"><div class="spec-title">Lost Phone Policy</div><div class="spec-value">{{ __('currency.symbol') }}5 eSIM Replacement Charge</div></div>
                                <div class="spec-row"><div class="spec-title">Technical Support</div><div class="spec-value"><span>24/7/365 Helpdesk</span><br><span>24/7/365 cs@gsm2go.com</span></div></div>
                            </div>
                            <div class="modal-footer specs-footer">
                                <button class="close-modal-btn" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

            @empty
                <p>No budget plans available</p>
            @endforelse

        </div>
    </div>
</section>