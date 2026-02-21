<div class="container-fluid header-banner">
    <div class="container">
        <div class="row display-flex align-items-center justify-content-center py-5">
            <div class="col-12 col-md-6 text-center title-container">
                <!-- <h5 class="banner-title">{{ __('home.cwb') }}</h5>-->
                <h2 class="banner-title-header">{{ __('home.get_conn') }}</h2>
                <!-- <p class="banner-title-content">{{ __('home.discount_line') }}</p>-->

                <div class="search-container">
                    <div class="search-left">
                        <i class="fa-solid fa-location-dot search-icon"></i>
                    </div>

                    <input type="text" class="search-input" placeholder="Search for destination" autocomplete="off"
                        id="countrySearch" wire:loading.attr="disabled">
<button type="button" class="search-clear-btn" id="clearBtn">
            <i class="fa-solid fa-xmark"></i>
        </button>
                    <!-- Loader -->
                    <div class="loader search-button" wire:loading wire:target="loadZonesByCountry">
                        <i class="fa fa-spinner fa-spin"></i> Loading...
                    </div>

                    <ul class="suggestions-list" id="suggestions" wire:loading.remove>

                        <!-- Header Row -->
                        <div class="dropdown-header">
                            <span>DESTINATIONS</span>
                            <span class="results-count">{{ count($countries) }} RESULTS</span>
                        </div>

                        @foreach ($countries as $country)
                            <li class="suggestion-item" data-country="{{ $country->id }}">

                                <div class="suggestion-left">
                                    <img src="{{ asset('images/country_flag/' . $country->flag) }}"
                                        alt="{{ $country->country_name }}" class="img-fluid country-flag"
                                        style="max-width:40px">
                                    <span>{{ $country->country_name }}</span>
                                </div>

                                <span class="country-badge">Country</span>

                            </li>
                        @endforeach

                    </ul>
                </div>


            </div>
            <div class="col-12 col-md-6 text-center position-relative">
                <img src="{{ asset('images/slider-img1.png') }}" alt="Slider Image"
                    class="img-fluid img1 d-none d-lg-block">
                <div class="d-lg-flex">
                    <img src="{{ asset('images/slider-img-2.png') }}" alt="Slider Image"
                        class="img-fluid img2 d-none d-lg-block">
                    <img src="{{ asset('images/slider-img-3.png') }}" alt="Slider Image" class="img-fluid img3">
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- tableCompare section -->
<section>
    <div class="container compare-table-section">

        <div class="title-container text-center">
            <!-- <h5 class="title">{{ __('home.compare') }}</h5>-->
            <h2 class="title-header">
                {{ $compare['heading'] }}
            </h2>
            <!-- <p class="title-content">
                {!! nl2br(e($compare['description'])) !!}
            </p>-->
        </div>

        <div class="row justify-content-center">
            <div class="col-12">
                <div class="table-compare">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="highlight-row">
                                    {{ $compare['data'][0][0] }}
                                </th>
                                <th>
                                    {{ $compare['data'][0][1] }}
                                </th>
                                <th>
                                    {{ $compare['data'][0][2] }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($compare['data'] as $index => $row)
                                @if ($index > 0)
                                    <tr>
                                        <td>{{ $row[0] }}</td>
                                        <td>{{ $row[1] }}</td>
                                        <td>{{ $row[2] ?: '-' }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>


<!-- pricingTable section -->
<section class="pricing-table-section bg-secondary-custom">
    <div class="container">

        <div class="title-container text-center">
            <!-- <h5 class="title">{{ __('home.pricing') }}</h5>-->
            <h2 class="title-header">
                {{ $pricingCompare['heading'] }}
            </h2>
            <p class="title-content">
                {{ $pricingCompare['description'] }}
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12">
                <div class="table-compare">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="highlight-row">
                                    {{ $pricingCompare['data'][0][0] }}
                                </th>
                                <th>{{ $pricingCompare['data'][0][1] }}</th>
                                <th>{{ $pricingCompare['data'][0][2] }}</th>
                                <th>{{ $pricingCompare['data'][0][3] }}</th>
                                <th>{{ $pricingCompare['data'][0][4] }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($pricingCompare['data'] as $index => $row)
                                @if ($index > 0)
                                    <tr>
                                        <td>{{ $row[0] }}</td>
                                        <td>{{ $row[1] }}</td>
                                        <td>{{ $row[2] }}</td>
                                        <td>{{ $row[3] }}</td>
                                        <td>{{ $row[4] }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>

                    </table>
                </div>
            </div>
        </div>

    </div>
</section>



@include('commons.plansworking')

<!-- staySafe section  -->
<section>
    <div class="container plans-working text-center text-md-start">
        <div class="row ">
            <div class="col-12 col-md-4">
                <div class="title-container ">
                    <!--  <h5 class="title">{{ __('home.know_us') }}</h5> -->
                    <h2 class="title-header">{{ __('home.connect_instantly') }}</h2>
                    <div class="customers">
                        <div class="avatars">
                            <img src="{{ asset('images/user1.png') }}" alt="User 1" />
                            <img src="{{ asset('images/user2.png') }}" alt="User 2" />
                            <img src="{{ asset('images/user3.png') }}" alt="User 3" />
                        </div>
                        <!-- <p class="title-content">{{ __('home.happy_cust') }}</p> -->
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <img src="{{ asset('images/stay-safe-online.png') }}" alt="stay-safe-online" class="img-fluid" />
            </div>

            <div class="col-12 col-md-4">
                <h2 class="title-header">{{ __('home.instant_purchase') }}</h2>
                <p class="title-content">{{ __('home.get_qr') }}</p>
                <div class="position-relative view-dest">
                    <a href="{{ url('/#topPlans') }}">
                        <button type="submit" class="view-button">
                            {{ __('home.get_start') }}<i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- whyGsm secyion  -->

<section class="why-gsm-section bg-secondary-custom">
    <div class="container">
        <div class="row display-flex align-items-end justify-content-between py-5">
            <div class="col-6 title-container">
                <!--  <h5 class="title">{{ __('home.why') }}</h5>-->
                <h2 class="title-header">{{ __('home.stay_conn') }}</h2>
            </div>
            <div class="col-6 position-relative view-dest">
                <a href="{{ url('/#topPlans') }}">
                    <button type="submit" class="view-button">
                        {{ __('home.choose_plan') }}<i class="fa-solid fa-arrow-right"></i>
                    </button>
                </a>
            </div>
        </div>

        <div class="row g-4">

            <!-- Card 1 -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 p-3">
                    <div class="">
                        <div class="card-icon mb-2">
                            <i class="fas fa-signal"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.find_plan') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.find_plan_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 p-3">
                    <div class="">
                        <div class="card-icon mb-2">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.easy_to_use') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.easy_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 p-3">
                    <div class="">
                        <div class="card-icon mb-2">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.avoid_roam') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.roaming_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 p-3">
                    <div class="">
                        <div class="card-icon mb-2">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.one_esim') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.one_esim_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 5 -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 p-3">
                    <div class="">
                        <div class="card-icon mb-2">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.never_out') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.never_out_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 6 -->
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100 p-3">
                    <div class="">
                        <div class="card-icon mb-2">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.global_plan') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.global_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>


<!-- aboutGsm section  -->
<section class="about-gsm-section">
    <div class="container">
        <div class=" title-container text-center mb-5">
            <!-- <h5 class="title">{{ __('home.about_us') }}</h5>-->
            <h2 class="title-header">{{ __('home.whats_unique') }}</h2>
        </div>

        <div class="row g-4 text-center">
            <!-- Card 1 -->
            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100 p-3">
                    <div class="d-flex flex-column align-items-center">
                        <div class="card-icon mb-2">
                            <i class="fas fa-signal"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.uk_num') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.uk_num_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100 p-3">
                    <div class="d-flex flex-column align-items-center">
                        <div class="card-icon mb-2">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.keeper') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.keeper_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100 p-3">
                    <div class="d-flex flex-column align-items-center">
                        <div class="card-icon mb-2">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.esim_install') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.esim_install_text') }}

                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100 p-3">
                    <div class="d-flex flex-column align-items-center">
                        <div class="card-icon mb-2">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="fw-bold">{{ __('home.phone_list') }}</h5>
                            <p class="text-muted small">
                                {{ __('home.phone_list_text') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>