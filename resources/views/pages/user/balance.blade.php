

<div class="balance-page">

    {{-- ── Hero Banner ─────────────────────────────────────────── --}}
    <div class="container balance-hero">
        <div class="">
            <div class="balance-hero-inner">
                <div class="balance-hero-icon">
                    <i class="fa-solid fa-chart-bar"></i>
                </div>
                <div>
                    <h1>Usage & Balance</h1>
                    <p>Real-time data from your active eSIM plan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">

        {{-- ── Error State ──────────────────────────────────────── --}}
        @if($error)
            <div class="balance-card">
                <div class="balance-error">
                    <div class="balance-error-icon">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <h5>Unable to load balance</h5>
                    <p>{{ $error }}</p>
                </div>
            </div>

        {{-- ── Loading Skeleton ─────────────────────────────────── --}}
        @elseif($loading)
            <div class="balance-card">
                <div class="balance-stats-grid">
                    @foreach(range(1,3) as $i)
                    <div class="balance-stat-cell">
                        <div class="skeleton skeleton-value"></div>
                        <div class="skeleton skeleton-sub"></div>
                        @if($i === 1)
                            <div class="skeleton skeleton-bar"></div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

        {{-- ── Balance Data ──────────────────────────────────────── --}}
        @else
            <div class="balance-card">

                {{-- ── Top Bar (ICCID + Status) ─────────────────── --}}
                <div class="balance-card-topbar">
                    <div>
                        <div class="iccid-label">
                            <i class="fa-solid fa-sim-card"></i>
                            eSIM Identifier
                        </div>
                        <div class="iccid-value">{{ $order->msisdn ?? '—' }}</div>
                    </div>
                    <div class="balance-status-badge {{ $isActive ? 'active' : 'expired' }}">
                        {{ $statusText }}
                    </div>
                </div>

                {{-- ── 3-Column Stats ────────────────────────────── --}}
                <div class="balance-stats-grid">

                    {{-- Data Remaining --}}
                    <div class="balance-stat-cell data-cell">
                        <div class="stat-label">
                            <i class="fa-solid fa-wifi"></i>
                            Data Remaining
                        </div>
                        <div class="stat-value">
                            {{ number_format($dataRem, 1) }}<span class="unit">GB</span>
                        </div>
                        <div class="stat-sub">{{ number_format($dataUsed, 1) }} GB used</div>

                        @if($dataTotal > 0)
                            <div class="data-progress-wrap">
                                <div class="data-progress-track">
                                    <div class="data-progress-fill" style="width: {{ $dataPct }}%"></div>
                                </div>
                                <div class="data-progress-labels">
                                    <span>{{ $dataPct }}% remaining</span>
                                    <span>{{ number_format($dataTotal, 1) }} GB total</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Voice Minutes --}}
                    <div class="balance-stat-cell">
                        <div class="stat-label">
                            <i class="fa-solid fa-phone"></i>
                            Voice Minutes
                        </div>
                        <div class="stat-value">
                            {{ $callOut }}<span class="unit">Min</span>
                        </div>
                        <div class="stat-sub">
                            {{ $callOutTotal > 0 ? 'of ' . $callOutTotal . ' total' : 'Not included' }}
                        </div>
                    </div>

                    {{-- SMS Remaining --}}
                    <div class="balance-stat-cell">
                        <div class="stat-label">
                            <i class="fa-solid fa-comment-sms"></i>
                            SMS Remaining
                        </div>
                        <div class="stat-value">{{ $smsRem }}</div>
                        <div class="stat-sub">
                            {{ $smsTotal > 0 ? 'of ' . $smsTotal . ' total' : 'Not included' }}
                        </div>
                    </div>

                </div>

                {{-- ── Bonus Data Row ────────────────────────────── --}}
                @if($bonusTotal > 0)
                    <div class="balance-bonus-row">
                        <div class="bonus-tag">
                            <i class="fa-solid fa-gift"></i>
                            Bonus Data
                        </div>
                        <div class="bonus-bar-wrap">
                            <div class="bonus-bar-track">
                                <div class="bonus-bar-fill" style="width: {{ $bonusPct }}%"></div>
                            </div>
                        </div>
                        <div class="bonus-amount">
                            {{ number_format($bonusRem, 1) }} GB
                            <span>/ {{ number_format($bonusTotal, 1) }} GB</span>
                        </div>
                    </div>
                @endif

                {{-- ── Expiry Row ────────────────────────────────── --}}
                <div class="balance-expiry-row">
                    <div class="expiry-block">
                        <div class="expiry-icon-wrap">
                            <i class="fa-regular fa-calendar-days"></i>
                        </div>
                        <div>
                            <div class="expiry-label">Plan Expiry</div>
                            <div class="expiry-date">
                                @if($endTime && $endTime !== '0000-00-00 00:00:00')
                                    {{ \Carbon\Carbon::parse($endTime)->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($expiryLabel)
                        <div class="expiry-remaining {{ $expiryClass }}">
                            <i class="fa-solid fa-clock"></i>
                            {{ $expiryLabel }}
                        </div>
                    @endif
                </div>

                {{-- ── Footer / CTA ──────────────────────────────── --}}
                <div class="balance-card-footer">
                    <div class="balance-plan-name">
                        <i class="fa-solid fa-layer-group"></i>
                        Current plan: <strong>{{ $planName }}</strong>
                    </div>
                   
                </div>

            </div>
        @endif

    </div>
</div>