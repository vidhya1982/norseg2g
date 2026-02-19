<div class="container py-5 business">

    <!-- HERO -->
    <div class="business-hero">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="fw-bold">
                    {{ __('staticpages/business.hero.title') }}
                </h1>

                <p class="mt-3 text-muted fs-5">
                    {{ __('staticpages/business.hero.line1') }}
                </p>

                <p class="text-muted">
                    {{ __('staticpages/business.hero.line2') }}
                </p>
            </div>
        </div>
    </div>

    <!-- WHY GSM2GO -->
    <div class="section-space">
        <h2 class="fw-bold text-center mb-4">
            {{ __('staticpages/business.why.title') }}
        </h2>

        <div class="row g-4">
            @foreach (__('staticpages/business.why.items') as $item)
                <div class="col-md-4">
                    <div class="card step-card p-4 h-100">
                        <h5 class="fw-bold">{{ $item['title'] }}</h5>
                        <p class="text-muted mb-0">{{ $item['desc'] }}</p>
                    </div>
                </div>
                
            @endforeach
        </div>
    </div>

    <!-- HOW IT WORKS -->
    <div class="section-space">
        <h2 class="fw-bold text-center mb-5">
            {{ __('staticpages/business.how.title') }}
        </h2>

        <div class="row g-4">
            @foreach (__('staticpages/business.how.steps') as $i => $step)
                <div class="col-md-{{ $i < 3 ? '4' : '6' }}">
                    <div class="card step-card p-4 h-100">
                        <div class="step-number">{{ $i + 1 }}</div>
                        <p class="mb-0">{{ $step }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- SPEED HIGHLIGHT -->
    <div class="section-space ">
        <div class="row align-items-center highlight-box text-center ">
           <div class="col-md-6 col-12">
             <h2 class="fw-bold">
                {{ __('staticpages/business.speed.title') }}
            </h2>
            <p class="fs-5 mt-2 mb-0">
                {{ __('staticpages/business.speed.desc') }}
            </p>
           </div>
            <div class="col-md-6 col-12 mt-md-0 mt-3">
              <img src="{{ asset('images/dashboard-postpaid-gsm2go.png') }}" alt="dashboard-postpaid-gsm2go"
                    class="img-fluid rounded dashboard-postpaid-gsm2go" />
        </div>
        </div>
       
    </div>

    <!-- CTA -->
    <div class="section-space text-center">
        <div class="cta-box">
            <h2 class="fw-bold">
                {{ __('staticpages/business.cta.title') }}
            </h2>

            <p class=" mt-3 fs-5">
                {{ __('staticpages/business.cta.desc') }}
            </p>

            <a href="{{ route('contact') }}" class="view-button mt-3">
                {{ __('staticpages/business.cta.button') }}
            </a>
        </div>
    </div>

</div>
