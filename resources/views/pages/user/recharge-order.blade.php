<div class="dashboard-page page-background">
    <div class="container py-4">
        @if(!$order)
            <div class="alert alert-danger">
                No rechargeable plan found for this number.
            </div>
        @else

            <div class="recharge-card">
                <h3 class="fw-bold mb-4">Quick Recharge</h3>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Customer Name:</strong> {{ $order->activationName ?? auth()->user()->name }}</p>
                        <p><strong>Country / Region:</strong> {{ $order->plan?->zone?->zone_name }}</p>
                        <p><strong>ICCID No:</strong> {{ $order->iccid?->ICCID }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phone Number:</strong> {{ $order->msisdn }}</p>
                        <p><strong>Plan Name:</strong> {{ $order->plan?->GB }}GB, {{ $order->plan?->Days }} Days</p>
                        <p><strong>Activation Date:</strong> {{ $order->getRawOriginal('plan_start_date') }}</p>
                        <p><strong>Expiration Date:</strong> {{ $order->getRawOriginal('plan_end_date') }}</p>
                    </div>
                </div>

                <hr>

                <div class="addons-section mt-3">
                    <h5>Addons</h5>
                    <p>
                        Auto-topup:
                        <span class="status-pill {{ $order->autorenew ? 'status-success' : 'status-failed' }}">
                            {{ $order->autorenew ? 'On' : 'Off' }}
                        </span>
                    </p>
                </div>

                @if($order->status !== 'ACTIVE')
                    <div class="alert d-flex align-items-start gap-2 mt-3"
                        style="background:#fffbeb;border:1px solid #f59e0b;border-radius:8px;padding:.75rem 1rem;">
                        <i class="fa-solid fa-triangle-exclamation mt-1" style="color:#f59e0b;flex-shrink:0;"></i>
                        <div>
                            <strong style="color:#92400e;">Plan Not Active</strong>
                            <p class="mb-0 small" style="color:#78350f;">
                                Your current plan status is <strong>{{ $order->status }}</strong>.
                                Recharge and Change Plan options are only available for active plans.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="mt-4 d-flex gap-3">
                    @if($order->status === 'ACTIVE')
                        <button wire:click="openChangePlan" wire:loading.attr="disabled" class="plan-btn">
                            <span wire:loading.remove wire:target="openChangePlan">Change Plan</span>
                            <span wire:loading wire:target="openChangePlan">
                                <span class="spinner-border spinner-border-sm me-1"></span> Loading…
                            </span>
                        </button>
                        <button wire:click="renewPlan" wire:loading.attr="disabled" class="plan-btn">
                            <span wire:loading.remove wire:target="renewPlan">Renew Existing Plan</span>
                            <span wire:loading wire:target="renewPlan">
                                <span class="spinner-border spinner-border-sm me-1"></span> Please wait…
                            </span>
                        </button>
                    @else
                        <button class="plan-btn" disabled>Change Plan</button>
                        <button class="plan-btn" disabled>Renew Existing Plan</button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ══ CHANGE PLAN MODAL ══ --}}
    @if($showChangePlanModal)

        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1040;" wire:click="closeChangePlan"></div>

        <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
                        z-index:1050;background:#fff;border-radius:12px;width:92%;max-width:540px;
                        box-shadow:0 8px 32px rgba(0,0,0,0.18);display:flex;flex-direction:column;
                        max-height:88vh;">

            <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;flex-shrink:0;">
                <h5 class="mb-0">
                    <i class="fa-solid fa-globe me-2 text-success"></i> Select a Plan
                </h5>
                <button wire:click="closeChangePlan" class="btn-close"></button>
            </div>

            <div style="padding:.75rem 1.25rem;border-bottom:1px solid #e5e7eb;flex-shrink:0;">
                <input type="text" id="plan-search" oninput="filterPlans(this.value)" placeholder="Search zone or plan..."
                    class="form-control form-control-sm" autocomplete="off">
            </div>

            <div id="plans-container" style="padding:1rem 1.25rem;overflow-y:auto;flex:1;">
                @forelse($availablePlans as $zoneName => $plans)
                    <div class="zone-group" data-zone="{{ strtolower($zoneName) }}">
                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                                            letter-spacing:.05em;color:#6b7280;padding:.5rem 0 .35rem;margin-top:.4rem;">
                            <i class="fa-solid fa-location-dot me-1 text-success"></i>
                            {{ $zoneName }}
                        </div>

                        @foreach($plans as $plan)
                            <div class="plan-row" data-id="{{ $plan['id'] }}" data-price="{{ $plan['price'] }}"
                                data-label="{{ $plan['label'] }}" data-zone="{{ $zoneName }}"
                                data-search="{{ strtolower($plan['label'] . ' ' . $zoneName) }}" onclick="selectPlanJS(this)" style="display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;
                                                   border-radius:8px;margin-bottom:.4rem;cursor:pointer;
                                                   border:1px solid #e5e7eb;background:#fff;">
                                <div class="plan-radio-dot" style="width:18px;height:18px;border-radius:50%;flex-shrink:0;
                                                        display:flex;align-items:center;justify-content:center;
                                                        border:2px solid #adb5bd;background:#fff;">
                                </div>
                                <span class="fw-semibold small flex-grow-1">{{ $plan['label'] }}</span>
                                <span style="font-weight:600;color:#198754;white-space:nowrap;">
                                    ${{ number_format($plan['price'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted text-center py-3">No plans available.</p>
                @endforelse

                <p id="no-results" style="display:none;" class="text-muted text-center py-3">
                    No results for "<span id="no-results-q"></span>"
                </p>
            </div>

            <div style="padding:.75rem 1.25rem;border-top:1px solid #e5e7eb;
                            border-bottom:1px solid #e5e7eb;flex-shrink:0;background:#f9fafb;">
                <p class="fw-semibold small mb-2 text-muted" style="text-transform:uppercase;letter-spacing:.05em;">
                    <i class="fa-solid fa-puzzle-piece me-1 text-success"></i> Addons
                </p>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <span class="fw-semibold small">Talk Time</span>
                        <span class="text-muted small ms-1">+${{ number_format($talkTimePrice, 2) }}</span>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="addon-talkt" wire:model="addonTalkTime"
                            onchange="updateFooter()" style="width:2.5rem;height:1.25rem;cursor:pointer;">
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-semibold small">Auto Topup</span>
                        <span class="text-muted small ms-1">Auto recharge when data runs out</span>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="addon-autot" wire:model="addonAutoTopup"
                            style="width:2.5rem;height:1.25rem;cursor:pointer;">
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:.75rem;padding:1rem 1.25rem;flex-shrink:0;">
                <div id="selected-info" class="text-muted small me-auto" style="display:none;">
                    <i class="fa-solid fa-circle-check text-success me-1"></i>
                    <span id="selected-label"></span>
                    <strong class="text-success ms-1" id="selected-price"></strong>
                </div>

                <button wire:click="closeChangePlan" class="btn btn-secondary btn-sm">Cancel</button>

                <button id="confirm-plan-btn" onclick="confirmPlanJS()" class="plan-btn btn-sm opacity-50" disabled>
                    <span id="confirm-btn-text">Confirm & Pay</span>
                </button>
            </div>

        </div>

    @endif

    {{-- ✅ Script ALWAYS outside @if — stays in DOM, always defined --}}
  <script>
    let selectedPlan = {
        id: null,
        price: 0,
        label: '',
        zone: ''
    };

    const TALK_TIME_PRICE = {{ $talkTimePrice ?? 10 }};

    function selectPlanJS(row) {

        // Reset all rows
        document.querySelectorAll('.plan-row').forEach(r => {
            r.style.borderColor = '#e5e7eb';
            r.style.background  = '#fff';

            const dot = r.querySelector('.plan-radio-dot');
            if (dot) {
                dot.style.borderColor = '#adb5bd';
                dot.style.background  = '#fff';
                dot.innerHTML = '';
            }
        });

        // Highlight selected row
        row.style.borderColor = '#198754';
        row.style.background  = '#f0fdf4';

        const dot = row.querySelector('.plan-radio-dot');
        if (dot) {
            dot.style.borderColor = '#198754';
            dot.style.background  = '#198754';
            dot.innerHTML = '<i class="fa-solid fa-check" style="font-size:8px;color:#fff;"></i>';
        }

        selectedPlan.id    = parseInt(row.dataset.id);
        selectedPlan.price = parseFloat(row.dataset.price);
        selectedPlan.label = row.dataset.label;
        selectedPlan.zone  = row.dataset.zone ?? '';

        updateFooter();
    }

    function updateFooter() {

        if (!selectedPlan.id) return;

        const talkToggle = document.getElementById('addon-talkt');
        const talkEnabled = talkToggle ? talkToggle.checked : false;

        const total = selectedPlan.price + (talkEnabled ? TALK_TIME_PRICE : 0);

        const info = document.getElementById('selected-info');
        if (info) info.style.display = '';

        const lbl = document.getElementById('selected-label');
        if (lbl) lbl.textContent = selectedPlan.label;

        const prc = document.getElementById('selected-price');
        if (prc) prc.textContent = '$' + total.toFixed(2);

        const btn = document.getElementById('confirm-plan-btn');
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }

        const txt = document.getElementById('confirm-btn-text');
        if (txt) txt.textContent = 'Confirm & Pay — $' + total.toFixed(2);
    }

    function filterPlans(query) {

        const q = query.toLowerCase().trim();
        let visibleCount = 0;

        document.querySelectorAll('.zone-group').forEach(group => {

            const rows = group.querySelectorAll('.plan-row');
            let groupVisible = 0;

            rows.forEach(row => {
                const match = !q || row.dataset.search.includes(q);
                row.style.display = match ? '' : 'none';
                if (match) groupVisible++;
            });

            group.style.display = groupVisible > 0 ? '' : 'none';
            visibleCount += groupVisible;
        });

        const noRes = document.getElementById('no-results');
        if (noRes) noRes.style.display = visibleCount === 0 ? '' : 'none';

        const noResQ = document.getElementById('no-results-q');
        if (noResQ) noResQ.textContent = query;
    }

   function confirmPlanJS() {

    if (!selectedPlan.id) return;

    const btn = document.getElementById('confirm-plan-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    // Get Livewire component instance properly
    const component = Livewire.first();

    if (!component) {
        console.error('Livewire component not found');
        return;
    }

    component.call(
        'confirmChangePlan',
        selectedPlan.id,
        selectedPlan.price,
        selectedPlan.label,
        selectedPlan.zone ?? ''
    );
}
</script>

</div>