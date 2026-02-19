<div class="dashboard-page page-background">
    <div class="row container py-4">

        <div class="col-lg-3 d-none d-lg-block user-panel-sidebar">
            <livewire:user.sidebar />
        </div>

        <div class="col-12 d-lg-none mb-3">
            @include('pages.user.common.mobile-tabs')
        </div>
        <div class="col-lg-9 col-12 order-wrapper">

            <!-- HEADER -->
            <div class="user-box  fade-in">
                <h2>Order Detail</h2>
                <p class="subtitle">Dear, {{ auth()->user()->fname }}{{ auth()->user()->lname }}</p>
                <p class="lead">
                    Thank you for purchasing a <strong>gsm2go eSIM</strong>.
                </p>
            </div>

            <!-- INTRO -->
            <div class="order-detail-card slide-up">


                <div class="badge-row">
                    <p>With the new Universal Link, there is no need to scan the QR.</p>
                    <p> When ready to activate, follow the instructions below.</p>
                    <p> Please note scanning the QR activates the eSIM and your plan duration then begins.</p>
                    <span> Make sure your phone is connected to the Internet</span>
                    <span>Please note that activation starts your plan</span>
                </div>
            </div>

            <!-- UNIVERSAL LINK -->
            <div class="order-detail-card activation-card zoom-in">
                <h3>Instant Install & Activation</h3>

                <div>
                    <p>iPhone users: please open this URL in the Safari web browser: https://t.ly/21857976489</p>
                    <p>Android users: please open this URL in your Android phone: https://t.ly/21857941220</p>
                </div>
            </div>

            <!-- QUICK STEPS -->
            <div class="order-detail-card slide-up">
                <h3>Very Quick Instructions</h3>
                <ul class="steps p-0">
                    <li>Make sure your phone is connected to the Internet</li>
                    <li>Scan the QR or use the universal Link <span class="text-success">(iPhone Link / Android
                            Link)</span></li>
                    <li>Set “Cellular Data” to be used from your gsm2go (“Safe Travels” or sometimes Secondary or
                        Business) eSIM.</li>
                    <li><span class="text-danger">Enable Data roaming </span>Settings / Cellular (or Mobile Data) /
                        Under SIMs, tap your secondary eSIM / Tap Data Roaming On</li>
                    <li><span class="text-danger">Android: Settings,</span> search for Roaming. Enable Roaming</li>
                </ul>
            </div>

            <div class="activation-card zoom-in">
                <h5>QR Code for your gsm2go eSIM:</h5>
            </div>

            <!-- ORDER SUMMARY -->
            <div class="order-summary slide-up">
                <h3>Order Summary</h3>

                <div class="summary-grid">
                    <div><span>Order ID</span><strong>{{ $order->id }}</strong></div>
                    <div><span>Plan Name</span><strong> {{ $order->plan->zone->zone_name ?? 'N/A' }}
</strong></div>
                    <div><span>duration, and Data</span><strong>{{ $order->plan?->name }}</strong></div>
                    <div><span>Total Paid</span><strong>{{ $order->usdFormatted }}</strong></div>
                    <div>*When your usage reaches 70%, we will send you an email (and text) notification with a link to
                        topup.</div>
                    <div>Short code to check your balance: dial 1099</div>
                </div>
                <div class=" slide-up">
                    <h5>Your eSIM Data</h5>

                    <div class="copy-field">
                        <code id="iccid">{{ $order->iccid?->ICCID }}</code>
                    </div>
                </div>
            </div>

            <div class="support-box">
                <p><strong>Safe Travels from</strong> <span class="brand">gsm2go</span></p>
                <p>WhatsApp: <a href="https://wa.me/447624041999">+447624041999</a></p>
                <p>Email: <a href="mailto:cs@gsm2go.com">cs@gsm2go.com</a></p>
                <p>Shortcode: 154 from your gsm2go eSIM</p>
                <p>
                    Phone:
                    <a href="tel:+442036952554">+442036952554</a> |
                    <a href="tel:+19172101233">+19172101233</a>
                </p>
            </div>



            <!-- ACCORDION -->
            <!-- TABS -->
            <div class="d-flex gap-3 mb-4">
                <button class="btn btn-outline-primary rounded-pill px-4 tab-btn active"
                    onclick="showGuideTab('iphone', this)">
                    {{ __('staticpages/guide.tabs.iphone') }}
                </button>

                <button class="btn btn-outline-primary rounded-pill px-4 tab-btn"
                    onclick="showGuideTab('android', this)">
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
            <div class="apn-guide">

    <p class="intro-text">
        Now switch off WiFi (for testing cellular data) and go to airplane mode for a few seconds.
        Then turn it off and wait for the phone to register on the network.
    </p>

    <p class="note-text">
        If data roaming is ON, the indicator will show <strong>4G / LTE / 5G</strong>.
    </p>

    <div class="section-title">APN Setup (Android)</div>

    <ul class="steps-list">
        <li><strong>Settings</strong></li>
        <li>Network & Internet (or Connections)</li>
        <li>SIMs / SIM cards / Mobile network</li>
        <li>Select your gsm2go (Safe Travels) eSIM</li>
        <li>Tap <strong>Access Point Names (APN)</strong></li>
        <li>Add a new APN</li>
        <li>Tap <strong>+</strong> or <strong>Add</strong></li>
        <li>Enter <code>mobiledata</code> as APN & APN Name</li>
        <li>Leave everything else blank</li>
        <li>Tap Save</li>
        <li>Select the new APN (only one active)</li>
    </ul>

    <div class="sub-section">
        <span>Samsung Galaxy (One UI)</span>
        <p>Settings → Connections → Mobile networks → Access Point Names</p>
    </div>

    <div class="sub-section">
        <span>Google Pixel</span>
        <p>Settings → Network & Internet → SIMs → eSIM → Access Point Names</p>
    </div>

    <div class="section-title">After APN Setup</div>

    <ul class="steps-list warning">
        <li>Data roaming → <strong>ON</strong></li>
        <li>Mobile data → <strong>ON</strong></li>
        <li>Set default data SIM to roaming eSIM</li>
        <li>Toggle Airplane mode ON → OFF or restart</li>
    </ul>

    <div class="final-check">
        <p>If data still doesn’t work:</p>
        <ul>
            <li>Toggle Airplane mode ON → OFF</li>
            <li>Restart the phone</li>
            <li>Temporarily force LTE / 4G only</li>
        </ul>
    </div>

</div>


        </div>
    </div>
</div>