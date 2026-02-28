(function () {
    'use strict';

    var _intentId      = null;
    var _clientSecret  = null;
    var _activeMethod  = 'card';
    var _dropinElement = null;
    var _sdkInitialized = false;

    // ─────────────────────────────────────────────────────────────────────────
    //  Payment Method Selector
    // ─────────────────────────────────────────────────────────────────────────

    window.selectPayMethod = function (method) {
        _activeMethod = method;

        // Sync Livewire property
        if (window.Livewire) {
            var wireId = document.querySelector('[wire\\:id]')?.getAttribute('wire:id');
            Livewire.find(wireId)?.set('paymentMethod', method);
        }

        // Sync radio buttons
        document.querySelectorAll('input[name="pay"]').forEach(function (r) {
            r.checked = (r.value === method);
        });

        // Highlight selected row
        document.querySelectorAll('.payment-option-row').forEach(function (row) {
            row.classList.remove('payment-option-selected');
        });
        var selectedRow = document.getElementById('method-' + method);
        if (selectedRow) selectedRow.classList.add('payment-option-selected');

        // If payment already initialized, remount with new method
        if (_intentId && _clientSecret) {
            remountDropin(method);
        }
    };

    // ─────────────────────────────────────────────────────────────────────────
    //  Livewire: listen for startPayment event
    // ─────────────────────────────────────────────────────────────────────────

    document.addEventListener('livewire:init', function () {
        Livewire.on('startPayment', function (payload) {
            var data      = Array.isArray(payload) ? payload[0] : payload;
            _intentId     = data?.intentId     ?? null;
            _clientSecret = data?.clientSecret ?? null;

            if (data?.method) {
                _activeMethod = data.method;
            }

            console.log('[Airwallex] startPayment received', {
                intentId: _intentId,
                method:   _activeMethod,
            });

            if (!_intentId || !_clientSecret) {
                console.error('[Airwallex] Missing intentId or clientSecret', data);
                return;
            }

            // Show wrapper, hide init button, show hint
            var wrapper = document.getElementById('airwallex-element-wrapper');
            var initBtn = document.getElementById('pay-now-init-btn');
            var hint    = document.getElementById('card-entry-hint');
            if (wrapper) wrapper.style.display = 'block';
            if (initBtn) initBtn.style.display  = 'none';
            if (hint)    hint.style.display     = 'block';

            setTimeout(function () {
                remountDropin(_activeMethod);
            }, 400);
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    //  Remount Drop-in with specific method only
    // ─────────────────────────────────────────────────────────────────────────

    function remountDropin(method) {
        // Unmount previous
        if (_dropinElement) {
            try { _dropinElement.unmount(); } catch (e) {}
            _dropinElement = null;
        }

        // Clear container
        var container = document.getElementById('airwallex-dropin');
        if (container) container.innerHTML = '';

        mountDropin(method);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Mount Drop-in with ONLY the selected method
    // ─────────────────────────────────────────────────────────────────────────

    async function mountDropin(method) {
        var container = document.getElementById('airwallex-dropin');
        if (!container) {
            console.error('[Airwallex] #airwallex-dropin container not found');
            return;
        }

        hideAirwallexError();

        try {
          if (!_sdkInitialized) {
    await window.AirwallexComponentsSDK.init({
        env: 'demo',
        enabledElements: ['payments'],
    });
    _sdkInitialized = true;
    console.log('[Airwallex] SDK initialized ✅');
}


            // Build options — pass ONLY the selected method
            var elementOptions = {
                intent_id:     _intentId,
                client_secret: _clientSecret,
                currency:      'USD',
                methods:       [method],  // ✅ Only show selected method
                save_card_for_future_use: true,
            };

            // Add required options for wallet methods
            if (method === 'applepay') {
                elementOptions.applePayRequestOptions = { countryCode: 'US' };
            }
            if (method === 'googlepay') {
                elementOptions.googlePayRequestOptions = { countryCode: 'US' };
            }

            _dropinElement = await window.AirwallexComponentsSDK.createElement('dropIn', elementOptions);

            _dropinElement.mount('airwallex-dropin');

            _dropinElement.on('ready', function () {
                console.log('[Airwallex] Drop-in ready for method:', method, '✅');
            });

            _dropinElement.on('success', function () {
                console.log('[Airwallex] Payment success ✅');
                var wireId = document.querySelector('[wire\\:id]')?.getAttribute('wire:id');
                Livewire.find(wireId)?.call('handlePaymentSuccess', _intentId);
            });

            _dropinElement.on('error', function (e) {
                console.error('[Airwallex] Drop-in error:', e);
                showAirwallexError(e?.detail?.message || 'Payment failed. Please try again.');
            });

        } catch (err) {
            console.error('[Airwallex] mountDropin failed:', err);
            showAirwallexError('Payment form failed to load. Please refresh and try again.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    function showAirwallexError(msg) {
        var el = document.getElementById('airwallex-error');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }

    function hideAirwallexError() {
        var el = document.getElementById('airwallex-error');
        if (el) el.style.display = 'none';
    }

})();