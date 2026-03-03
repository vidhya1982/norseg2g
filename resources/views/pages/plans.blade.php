<!-- topPlans section -->
<section id="topPlans">
    <div class="container top-plans">
        <div class="row display-flex align-items-center justify-content-center pt-5">

            <div class="col-6 title-container">
                <!-- <h5 class="title">{{ __('home.know_us') }}</h5>-->
                <h2 class="title-header">{{ __('home.top_plans') }}</h2>
            </div>

            <div class="col-6 text-end position-relative view-btn">

            </div>

            <div class="row justify-content-center mt-3">

                @forelse ($zones as $zone)
                    <div class="col-12 col-md-6 col-lg-4" data-countries="{{ $zone->countries }}">
                        <a href="{{ route('plans-details', $zone->id) }}" class="planCard">

                            <div class="plan-card">
                                <!-- SAME IMAGE STRUCTURE -->
                                <img src="{{ asset('images/continent/' . $zone->image) }}" alt="{{ $zone->name }} Plan" />

                                <div class="card-overlay">
                                    <button class="explore-btn">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>

                                <div class="plan-label">
                                    <img src="{{ asset('images/' . $zone->zone_flag) }}" alt="{{ $zone->name }} Flag" />

                                    <span>{{ $zone->name }}</span>
                                    | from {{ number_format($zone->starting_price, 2) }} USD
                                </div>
                                <div class="spec-link" data-bs-toggle="modal" data-bs-target="#specsModal"
                                    onclick="event.preventDefault(); event.stopPropagation();">
                                    eSIM Specifications ›
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="modal fade" id="specsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content specs-modal">

                                <!-- HEADER -->
                                <div class="modal-header specs-header">
                                    <h4 class="modal-title">Europe eSIM Specifications</h4>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                                    </button>
                                </div>

                                <!-- BODY (Scrollable Area) -->
                                <div class="modal-body specs-body">

                                    <div class="spec-row">
                                        <div class="spec-title">Format</div>
                                        <div class="spec-value">eSIM (eUICC Profile)</div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Compatibility</div>
                                        <div class="spec-value link">See Compatibility List</div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Installation</div>
                                        <div class="spec-value">
                                            QR Code<br>
                                            Manual Activation Code<br>
                                            Automatic (iOS 17+)
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Multiple Devices</div>
                                        <div class="spec-value">
                                            No - eSIM Locked to First IMEI
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Data Activation</div>
                                        <div class="spec-value">
                                            Activation Date Selected on Checkout
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Data Speed</div>
                                        <div class="spec-value">
                                            5G and 4G LTE (Where available)
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Available Networks</div>
                                        <div class="spec-value">
                                            Various (See Individual Country Plans)
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Max Validity</div>
                                        <div class="spec-value">
                                            5 Days - 180 Days
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Wi-Fi Hotspot</div>
                                        <div class="spec-value">
                                            Available on all eSIM Plans<br>
                                            No restrictions
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Voice / SMS</div>
                                        <div class="spec-value">
                                            Available with Wi-Fi Calling
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">APN</div>
                                        <div class="spec-value">
                                            Automatic
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Usage Policy</div>
                                        <div class="spec-value">
                                            Personal cellular use only.<br>
                                            Use as fixed line or router is prohibited.<br>
                                            <span class="link">View AUP ›</span>
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Refund Policy</div>
                                        <div class="spec-value highlight">
                                            100% Money Back Guarantee
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Lost Phone Policy</div>
                                        <div class="spec-value">
                                            $5 eSIM Replacement Charge
                                        </div>
                                    </div>

                                    <div class="spec-row">
                                        <div class="spec-title">Technical Support</div>
                                        <div class="spec-value">
                                            <span>24/7/365 Helpdesk</span><br>
                                            <span>24/7/365 cs@gsm2go.com</span>
                                        </div>
                                    </div>

                                </div>

                                <!-- FOOTER -->
                                <div class="modal-footer specs-footer">
                                    <button class="close-modal-btn" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <h5>We don’t currently have plans for this destination.</h5>
                    </div>
                @endforelse

            </div>
        </div>
    </div>
</section>