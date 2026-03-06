<div class="container py-5 about">

    {{-- HERO --}}
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


    {{-- NORSE ATLANTIC --}}
    <div class="about-section row align-items-center">

        <div class="col-md-6">
            <h2 class="fw-bold mb-3">
                {{ __('staticpages/about.norse.title') }}
            </h2>

            <p>{{ __('staticpages/about.norse.text1') }}</p>
            <p>{{ __('staticpages/about.norse.text2') }}</p>
            <p>{{ __('staticpages/about.norse.text3') }}</p>
            <p>{{ __('staticpages/about.norse.text4') }}</p>
            <p>{{ __('staticpages/about.norse.text5') }}</p>
            <p>{{ __('staticpages/about.norse.text6') }}</p>
        </div>

        <div class="col-md-6 d-flex justify-content-end">
            <img src="{{ asset('images/about-norse.jpeg') }}" class="img-fluid shadow">
        </div>

    </div>


    {{-- GSM2GO --}}
    <div class="about-section row align-items-center flex-md-row-reverse">

        <div class="col-md-6">
            <h2 class="fw-bold mb-3">
                {{ __('staticpages/about.gsm2go.title') }}
            </h2>

            <p>{{ __('staticpages/about.gsm2go.text1') }}</p>
            <p>{{ __('staticpages/about.gsm2go.text2') }}</p>
            <p>{{ __('staticpages/about.gsm2go.text3') }}</p>
            <p>{{ __('staticpages/about.gsm2go.text4') }}</p>
            <p>{{ __('staticpages/about.gsm2go.text5') }}</p>
        </div>

        <div class="col-md-6">
            <img src="{{ asset('images/about-network.jpg') }}" class="img-fluid shadow">
        </div>

    </div>


    {{-- WHY GSM2GO --}}
    <div class="about-section">

        <h2 class="fw-bold text-center mb-5">
            {{ __('staticpages/about.why.title') }}
        </h2>

        <div class="row g-4">

            @foreach(__('staticpages/about.why.items') as $item)

                <div class="col-md-4">

                    <div class="about-card p-4 h-100">

                        <div class="icon-badge">
                            <i class="fa-solid fa-check"></i>
                        </div>

                        <p class="mb-0">
                            {{ $item }}
                        </p>

                    </div>

                </div>

            @endforeach

        </div>

    </div>


    {{-- CTA --}}
    <div class="about-section text-center">

        <div class="cta-box">

            <h2 class="fw-bold">
                {{ __('staticpages/about.cta.title') }}
            </h2>

            <p class="fs-5 mt-3">
                {{ __('staticpages/about.cta.text') }}
            </p>

            <a href="{{ url('/#topPlans') }}" class="btn btn-light mt-4 px-4 py-2 fw-semibold">
                {{ __('staticpages/about.cta.button') }}
            </a>

        </div>

    </div>

</div>