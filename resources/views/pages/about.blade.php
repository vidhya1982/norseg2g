<div class="container py-5 about">

    <!-- HERO -->
    <div class="about-hero text-center">
        <h1 class="fw-bold">
            {{ __('staticpages/about.hero.title') }}
            <span class="gradient-text">
                {{ __('staticpages/about.hero.highlight') }}
            </span>
        </h1>

        <p class="fs-5 text-muted mt-4">
            {{ __('staticpages/about.hero.subtitle') }}
        </p>
    </div>

    <!-- WHO WE ARE -->
    <div class="about-section row align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold mb-3">
                {{ __('staticpages/about.who.title') }}
            </h2>
            <p>
                {{ __('staticpages/about.who.text') }}
            </p>
        </div>
        <div class="col-md-6 d-flex justify-content-end">
            <img src="{{ asset('images/about-network.jpg') }}" class="img-fluid rounded-4 shadow" alt="">
        </div>
    </div>

    <!-- OUR JOURNEY -->
    <div class="about-section row align-items-center flex-md-row-reverse">
        <div class="col-md-6">
            <h2 class="fw-bold mb-3">
                {{ __('staticpages/about.journey.title') }}
            </h2>
            <p>
                {{ __('staticpages/about.journey.text') }}
            </p>
        </div>
        <div class="col-md-6">
            <img src="{{ asset('images/about-esim.png') }}" class="img-fluid rounded-4 shadow" alt="">
        </div>
    </div>

    <!-- OUR BELIEF -->
    <div class="about-section text-center">
        <h2 class="fw-bold mb-4 gradient-text">
            {{ __('staticpages/about.belief.title') }}
        </h2>
        <p class="fs-5">
            {{ __('staticpages/about.belief.text') }}
        </p>
    </div>

    <!-- WHY GSM2GO -->
    <div class="about-section">
        <h2 class="fw-bold text-center mb-5">
            {{ __('staticpages/about.why.title') }}
        </h2>

        <div class="row g-4">
            @foreach(__('staticpages/about.why.items') as $item)
                <div class="col-md-{{ $loop->index < 3 ? '4' : '6' }}">
                    <div class="about-card p-4 h-100">
                        <div class="icon-badge">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <p>{{ $item }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- RELATIONSHIPS -->
    <div class="about-section text-center">
        <h2 class="fw-bold mb-4">
            {{ __('staticpages/about.relationships.title') }}
        </h2>
        <p class="fs-5">
            {{ __('staticpages/about.relationships.subtitle') }}
        </p>

        <div class="row justify-content-center mt-4">
            @foreach(__('staticpages/about.relationships.points') as $point)
                <div class="col-md-3">✔ {{ $point }}</div>
            @endforeach
        </div>
    </div>

    <!-- CTA -->
    <div class="about-section text-center">
        <div class="cta-box">
            <h2 class="fw-bold">
                {{ __('staticpages/about.cta.title') }}
            </h2>
            <p class="fs-5 mt-3">
                {{ __('staticpages/about.cta.text') }}
            </p>
            <a href="{{ url('/#topPlans') }}" class="view-button">
                {{ __('staticpages/about.cta.button') }}
            </a>
        </div>
    </div>

</div>
