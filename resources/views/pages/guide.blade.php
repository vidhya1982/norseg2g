<div class="container py-5 guide">

    <!-- HERO -->
    <div class="guide-hero mb-5">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="fw-bold">
                    {{ __('staticpages/guide.hero.title') }}
                </h1>

                <h5 class="text-primary fw-semibold">
                    {{ __('staticpages/guide.hero.subtitle') }}
                </h5>

                <p class="mt-3 text-muted">
                    {{ __('staticpages/guide.hero.description') }}
                </p>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div class="d-flex gap-3 mb-4">
        <button class="btn btn-outline-primary rounded-pill px-4 tab-btn active" onclick="showGuideTab('iphone', this)">
            {{ __('staticpages/guide.tabs.iphone') }}
        </button>

        <button class="btn btn-outline-primary rounded-pill px-4 tab-btn" onclick="showGuideTab('android', this)">
            {{ __('staticpages/guide.tabs.android') }}
        </button>
    </div>

    <!-- ================= IPHONE ================= -->
    <div id="iphone" class="tab-section">

        <h3 class="fw-bold mb-4">
            {{ __('staticpages/guide.iphone.title') }}
        </h3>
        
        @foreach (__('staticpages/guide.iphone.steps') as $step)
            <div class="card step-card mb-3">
                <div class="card-body">
                    <h6 class="step-title">
                        {{ $step['title'] }}
                    </h6>

                    <ul class="mb-0">
                        @foreach ($step['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach

    </div>

    <!-- ================= ANDROID ================= -->
    <div id="android" class="tab-section d-none">

        <h3 class="fw-bold mb-4">
            {{ __('staticpages/guide.android.title') }}
        </h3>

        <div class="card step-card mb-3">
            <div class="card-body">
                <ul class="mb-0">
                    @foreach (__('staticpages/guide.android.steps') as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>

    <!-- DOWNLOAD -->
    <!-- <div class="card text-center mt-5 step-card">
        <div class="card-body py-5">
            <h4 class="fw-bold">
                {{ __('staticpages/guide.download.title') }}
            </h4>

            <a href="#" class="btn btn-primary rounded-pill px-4 mt-2">
                {{ __('staticpages/guide.download.button') }}
            </a>
        </div>
    </div> -->

</div>
