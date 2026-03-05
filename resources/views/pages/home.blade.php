<div class="container-fluid header-banner">
    <div class="container">
        <div class="row display-flex align-items-center justify-content-center py-5">
            <div class="col-12 col-md-6 text-center title-container">
                <!-- <h5 class="banner-title">{{ __('home.cwb') }}</h5>-->
                <h2 class="banner-title-header">{!! __('home.get_conn') !!}</h2>
                
                

              
                {{-- <div class="mt-5 text-start">
                <h5 class="banner-title-content">We cover the globe.</h5>  </div>
                <div class="search-container">

                    <div class="search-left">
                        <i class="fa-solid fa-location-dot search-icon"></i>
                    </div>

                    <input type="text" class="search-input" placeholder="Check if your destination is included"
                        autocomplete="off" id="countrySearch" wire:loading.attr="disabled">
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
                            <!-- <span class="results-count">{{ count($countries) }} RESULTS</span> -->
                        </div>

                        @foreach ($countries as $country)
                            <li class="suggestion-item" data-country="{{ $country->id }}">

                                <div class="suggestion-left">
                                    <img src="{{ asset('images/country_flag/' . $country->flag) }}"
                                        alt="{{ $country->country_name }}" class="img-fluid country-flag"
                                        style="max-width:40px">
                                    <span>{{ $country->country_name }}</span>
                                </div>

                                <span class="country-badge">covered</span>

                            </li>
                        @endforeach

                    </ul>

                </div>
                <div id="noZonesMessage" class="no-zones-message ">
                    <div class="no-zones-box text-danger">
                        <h5>sorry, this country is not yet covered</h5>
                    </div>
                </div> --}}



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


<section class="offer-section">
    <div class="container">

        <div class="title-container text-center">
            <h2 class="title-header">Choose your Fly Norse eSIM Promo</h2>
            <p class="title-content">Grab exclusive deals on selected plans</p>
        </div>

        <div class="row g-4">

            <!-- Card 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="offer-card">
                    <div class="offer-image">
                        <img src="{{ asset('images/gallery/Central Park2.jpg') }}" alt="Free eSIM Offer">
                    </div>

                    <div class="offer-content">
                        <div class="offer-header">
                            <h4>Get a Free 1 GB eSIM</h4>
                            <span class="tc">*T&Cs apply</span>
                        </div>

                        <p class="offer-desc">
                            FlyNorse Promo Code: FlyNorse1GB.
                        </p>

                        <div class="coupon-box">
                            FlyNorse1GB
                        </div>

                        <a href="#" class="offer-btn">
                            GET A FREE ESIM NOW
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="offer-card">
                    <div class="offer-image">
                        <img src="{{ asset('images/gallery/Regent Street London 1.jpg') }}" alt="Bonus Data Offer">
                    </div>

                    <div class="offer-content">
                        <div class="offer-header">
                            <h4>Get Bonus Data</h4>
                            <span class="tc">*T&Cs apply</span>
                        </div>

                        <p class="offer-desc">
                            Get 2 GB extra data for free (on GB Data Plans).
                        </p>

                        <div class="coupon-box">
                            FlyNorse2GB
                        </div>

                        <a href="#" class="offer-btn">
                            CLAIM BONUS DATA NOW
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="offer-card">
                    <div class="offer-image">
                        <img src="{{ asset('images/city-tile-fco.jpg') }}" alt="BOGO Offer">
                    </div>

                    <div class="offer-content">
                        <div class="offer-header">
                            <h4>Buy One Get One</h4>
                            <span class="tc">*T&Cs apply</span>
                        </div>

                        <p class="offer-desc">
                            Get two eSIMs for the price of one (15 days plans or more).
                        </p>

                        <div class="coupon-box">
                            FLYNORSEBOGO
                        </div>

                        <a href="#" class="offer-btn">
                            CLAIM BOGO OFFER NOW
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- topPlans section -->
<section id="topPlans">
    <div class="container top-plans">

        <h2 class="title-header">{{ __('home.unlimited_plans') }}</h2>

        <div class="row justify-content-center mt-3">

            @forelse ($unlimitedPlans as $plan)

                <div class="col-12 col-md-6 col-lg-4">

                   <a href="{{ route('plans-details', [
    'zone' => 1,
    'type' => 'unlimited',
    'days' => $plan->Days
]) }}" class="planCard">

                        <div class="plan-card">

                            @php
                                $dayImages = [
                                    5 => 'europe.png',
                                    10 => 'japan.png',
                                    21 => 'world.png',
                                ];
                            @endphp

                            <img src="{{ asset('images/continent/' . ($dayImages[$plan->Days] ?? 'europe.png')) }}">
                            <div class="card-overlay">
                                <button class="explore-btn">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>

                            <div class="plan-label">

                                <img src="{{ asset('images/' . $zone->zone_flag) }}">

                                <span>{{ $plan->Days }} Days</span>

                                | from {{ number_format($plan->USD, 2) }} {{ __('currency.code') }}

                            </div>

                        </div>

                    </a>

                </div>

            @empty
                <p>No unlimited plans available</p>
            @endforelse

        </div>
    </div>
</section>
<section id="topPlans">
    <div class="container top-plans">

        <h2 class="title-header">{{ __('home.Budget') }}</h2>

        <div class="row justify-content-center mt-3">

            @forelse ($budgetPlans as $plan)

                <div class="col-12 col-md-6 col-lg-4">

                   <a href="{{ route('plans-details', [
    'zone' => 1,
    'gb' => $plan->GB
]) }}" class="planCard">

                        <div class="plan-card">
                            @php
                                $imageMap = [
                                    3 => 'world.png',
                                    5 => 'europe.png',
                                    10 => 'uk.png',
                                    20 => 'world2.jpeg',
                                ];
                            @endphp
                            <img src="{{ asset('images/continent/' . ($imageMap[$plan->GB] ?? 'europe.png')) }}">

                            <div class="card-overlay">
                                <button class="explore-btn">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>

                            <div class="plan-label">

                                <img src="{{ asset('images/' . $zone->zone_flag) }}">

                                <span>{{ $plan->GB }} GB</span>

                                | {{ $plan->Days }} Days

                            </div>

                        </div>

                    </a>

                </div>

            @empty
                <p>No budget plans available</p>
            @endforelse

        </div>
    </div>
</section>
<!-- tableCompare section -->
{{-- 
<section class="bg-secondary-custom compare-section">
    <div class="container compare-table-section">

        <div class="title-container text-center">
            <h2 class="title-header">
                {{ $compare['heading'] }}
            </h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-12">
                <div class="compare-wrapper">

                    <div class="desktop-view">
                        <table class="compare-table">
                            <thead>
                                <tr>
                                    @foreach ($compare['data'][0] as $heading)
                                        <th>{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($compare['data'] as $index => $row)
                                    @if ($index > 0)
                                        <tr>
                                            @foreach ($row as $col)
                                                <td>{!! $col ?: '-' !!}</td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mobile-view">
                        @foreach ($compare['data'] as $index => $row)
                            @if ($index > 0)
                                <div class="compare-card">
                                    <h4 class="feature-title">{{ $row[0] }}</h4>

                                    <div class="compare-item winner">
                                        <span class="label">{{ $compare['data'][0][1] }}</span>
                                        <p>{!! $row[1] ?: '-' !!}</p>
                                    </div>

                                    <div class="compare-item other">
                                        <span class="label">{{ $compare['data'][0][2] }}</span>
                                        <p>{!! $row[2] ?: '-' !!}</p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                </div>
            </div>
        </div>

    </div>
</section>  --}} 


<!-- pricingTable section -->
 {{--  <section class="pricing-table-section ">
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

        <div class="table-compare pricing-compare-wrapper">
            <div class="table-scroll">
                <table class="table compare-table">
                    <thead>
                        <tr>
                            @foreach ($pricingCompare['data'][0] as $headIndex => $heading)
                                <th class="{{ $headIndex == 0 ? 'feature-head' : '' }}">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($pricingCompare['data'] as $index => $row)
                            @if ($index > 0)
                                <tr>
                                    @foreach ($row as $colIndex => $col)
                                        <td class="{{ $colIndex == 0 ? 'feature-col' : '' }}">
                                            {{ $col ?: '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section> --}}




@include('commons.plansworking')

<!-- staySafe section  -->
<section class="bg-secondary-custom">
    <div class="container plans-working text-center text-md-start ">
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
                    <a href="{{ route('plans') }}">
                        <button type="submit" class="view-button">
                            Choose the plan that works for you <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- whyGsm secyion  -->

<section class="why-gsm-section">
    <div class="container">
        <div class="row display-flex align-items-end justify-content-between py-5">
            <div class="col-6 title-container">
                <!--  <h5 class="title">{{ __('home.why') }}</h5>-->
                <h2 class="title-header">{{ __('home.stay_conn') }}</h2>
                <p class="title-header">we covers the globe</p>
            </div>
            <div class="col-6 position-relative view-dest">
                <a href="{{ route('plans') }}">
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