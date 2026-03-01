<div class="dashboard-page page-background">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center">

                    {{-- Icon --}}
                    <div class="mb-3">
                        <span style="display:inline-flex;align-items:center;justify-content:center;
                                     width:60px;height:60px;border-radius:50%;
                                     background:var(--primary-color);">
                            <i class="fa-solid fa-bolt" style="font-size:1.5rem;color:#fff;"></i>
                        </span>
                    </div>

                    <h4 class="fw-bold mb-1" style="color:var(--primary-color);">Quick Recharge</h4>
                    <p class="text-muted small mb-4">Enter your ICCID or phone number to renew your eSIM plan</p>

                    {{-- Input --}}
                    <div class="input-group mb-3 rounded-3 overflow-hidden"
                         style="border:1.5px solid #dee2e6;">
                        <span class="input-group-text bg-white border-0">
                            <i class="fa-solid fa-sim-card text-muted"></i>
                        </span>
                        <input
                            type="text"
                            wire:model.defer="search"
                            wire:keydown.enter="findOrder"
                            class="form-control border-0 shadow-none"
                            placeholder="ICCID / Phone Number"
                            autocomplete="off"
                        >
                        <button
                            wire:click="findOrder"
                            wire:loading.attr="disabled"
                            class="btn border-0 px-4 fw-bold text-white"
                            style="background:var(--accent-color);"
                        >
                            <span wire:loading.remove wire:target="findOrder">
                                <i class="fa-solid fa-arrow-right"></i>
                            </span>
                            <span wire:loading wire:target="findOrder">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </button>
                    </div>

                    {{-- Error --}}
                    @if($errorMsg)
                        <div class="alert alert-danger py-2 px-3 text-start small mb-3">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            {{ $errorMsg }}
                        </div>
                    @endif

                    <p class="text-muted mb-0" style="font-size:.78rem;">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Your ICCID is in your eSIM activation email
                    </p>

                </div>

            </div>
        </div>
    </div>
</div>