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