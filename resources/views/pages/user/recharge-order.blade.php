<div class="container py-4">

    <h3 class="mb-4 fw-bold">Recharge Order</h3>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <p class="text-muted mb-2">
                Plan: <strong>{{ $order->plan_moniker }}</strong>
            </p>

            <div class="mb-3">
                <label class="form-label">Recharge Amount (USD)</label>
                <input type="number" step="0.01"
                       class="form-control"
                       wire:model.defer="amount">
            </div>

            <button class="btn btn-success"
                    wire:click="recharge"
                    wire:loading.attr="disabled">
                Recharge Now
            </button>

            <a href="{{ route('user.orders') }}"
               class="btn btn-outline-secondary ms-2">
                Back
            </a>

        </div>
    </div>

</div>
