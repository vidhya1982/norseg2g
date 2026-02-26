/**
 * checkout-airwallex.js
 * Airwallex payment integration for checkout page.
 *
 * Usage (in Blade layout, after Airwallex SDK):
 *   <script src="https://checkout.airwallex.com/assets/elements.bundle.min.js"></script>
 *   <script src="{{ asset('js/checkout-airwallex.js') }}"></script>
 *
 * Livewire events consumed:  startPayment  (intentId, clientSecret, method)
 * Livewire methods called:   handlePaymentSuccess(intentId)
 */

(function () {
    'use strict';

    // ─────────────────────────────────────────────────────────────────────────
    //  State
    // ─────────────────────────────────────────────────────────────────────────

    var _intentId     = null;
    var _clientSecret = null;
    var _activeMethod = 'card';   // currently selected payment method
    var _elements     = {};       // mounted Airwallex elements keyed by method
    var isConfirming  = false;

    // ─────────────────────────────────────────────────────────────────────────
    //  Payment Method Selector
    // ─────────────────────────────────────────────────────────────────────────

    window.selectPayMethod = function (method) {
        _activeMethod = method;

        // Sync Livewire property so payNow() gets the correct method
        if (window.Livewire) {
            Livewire.find(
                document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
            )?.set('paymentMethod', method);
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

        // If elements already mounted (after payNow), switch visible element
        if (_intentId) {
            showMethodElement(method);
        }
    };

    // ─────────────────────────────────────────────────────────────────────────
    //  Show / Hide element containers
    // ─────────────────────────────────────────────────────────────────────────

    function showMethodElement(method) {
        ['airwallex-card', 'airwallex-applepay', 'airwallex-googlepay', 'airwallex-paypal']
            .forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

        var active = document.getElementById('airwallex-' + method);
        if (active) active.style.display = 'block';

        // Confirm button only for card (wallets confirm themselves)
        var btn = document.getElementById('airwallex-pay-btn');
        if (btn) btn.style.display = (method === 'card') ? 'block' : 'none';

        // Lazy-mount wallet if not yet mounted
        if (method !== 'card' && _intentId && !_elements[method]) {
            mountDropin(method);
        }
    }

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

            // Small delay to ensure DOM is ready before mounting
            setTimeout(mountAllElements, 400);
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    //  Mount all elements
    // ─────────────────────────────────────────────────────────────────────────

    function mountAllElements() {
        try {
            Airwallex.init({ env: 'demo', origin: window.location.origin });
        } catch (e) {
            console.warn('[Airwallex] init warning:', e);
        }

        if (_activeMethod === 'card') {
            mountCard();
        } else {
            mountDropin(_activeMethod);
        }

        showMethodElement(_activeMethod);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Card Element
    // ─────────────────────────────────────────────────────────────────────────

    function mountCard() {
        var container = document.getElementById('airwallex-card');
        if (!container || _elements['card']) return;

        try {
            var cardEl = Airwallex.createElement('card', {
                intent_id:     _intentId,
                client_secret: _clientSecret,
            });

            cardEl.mount('airwallex-card');
            _elements['card'] = cardEl;

            cardEl.on('ready', function () {
                console.log('[Airwallex] Card element ready ✅');
            });

            cardEl.on('change', function (e) {
                var btn = document.getElementById('airwallex-pay-btn');
                if (btn) btn.disabled = !(e && e.detail && e.detail.complete);
            });

            cardEl.on('error', function (e) {
                showAirwallexError((e?.detail?.message) || 'Card error');
            });

        } catch (err) {
            console.error('[Airwallex] mountCard failed:', err);
            showAirwallexError('Card payment not available. Please try again.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Dropin Element (PayPal / Google Pay / Apple Pay)
    // ─────────────────────────────────────────────────────────────────────────

    function mountDropin(method) {
        var containerId = 'airwallex-' + method;
        var container   = document.getElementById(containerId);
        if (!container || _elements[method]) return;

        // HTTPS check for wallet methods
        if (window.location.protocol !== 'https:' &&
            (method === 'applepay' || method === 'googlepay')) {
            container.innerHTML =
                '<p class="text-warning small p-2">⚠️ ' + method +
                ' requires HTTPS. Use Card payment for local testing.</p>';
            return;
        }

        var methodMap = {
            googlepay: 'googlepay',
            applepay:  'applepay',
            paypal:    'paypal',
        };

        try {
            var dropinEl = Airwallex.createElement('dropin', {
                intent_id:     _intentId,
                client_secret: _clientSecret,
                methods:       [methodMap[method]],
            });

            dropinEl.mount(containerId);
            _elements[method] = dropinEl;

            dropinEl.on('ready', function () {
                console.log('[Airwallex] dropin (' + method + ') ready ✅');
            });

            dropinEl.on('success', function (e) {
                console.log('[Airwallex] dropin (' + method + ') success', e);
                Livewire.find(
                    document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
                )?.call('handlePaymentSuccess', _intentId);
            });

            dropinEl.on('error', function (e) {
                var msg = (e?.detail?.message) || (method + ' payment failed');
                console.error('[Airwallex] dropin error:', e);
                showAirwallexError(msg);
            });

        } catch (err) {
            console.error('[Airwallex] dropin (' + method + ') mount failed:', err.message || err);
            var isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
            var msg = isLocalhost
                ? '⚠️ ' + method + ' is not supported on localhost. Use Card or test via ngrok (HTTPS).'
                : '⚠️ ' + method + ' unavailable. Please use Card.';
            container.innerHTML = '<p class="text-warning small p-2">' + msg + '</p>';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Confirm Card Payment
    // ─────────────────────────────────────────────────────────────────────────

    window.confirmAirwallexPayment = function () {
        if (isConfirming) return;

        var cardEl = _elements['card'];
        if (!cardEl) { showAirwallexError('Payment form not ready.'); return; }

        if (!_intentId || !_clientSecret) {
            showAirwallexError('Session expired. Please refresh and try again.');
            return;
        }

        isConfirming = true;
        hideAirwallexError();

        var btn = document.getElementById('airwallex-pay-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Processing...'; }

        console.log('[Airwallex] Confirming card payment, intent:', _intentId);

        Airwallex.confirmPaymentIntent({
            element:       cardEl,
            id:            _intentId,
            client_secret: _clientSecret,
        }).then(function (result) {
            console.log('[Airwallex] Confirm result:', result);

            if (result && result.error) {
                throw new Error(result.error.message || 'Payment declined.');
            }

            var status = result?.paymentIntent?.status ?? result?.status ?? '';
            console.log('[Airwallex] Payment status:', status);

            if (status === 'SUCCEEDED' || status === 'REQUIRES_CAPTURE') {
                Livewire.find(
                    document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
                )?.call('handlePaymentSuccess', _intentId);
            } else {
                throw new Error('Unexpected payment status: ' + status);
            }

        }).catch(function (err) {
            console.error('[Airwallex] confirm error:', err);
            showAirwallexError(err.message || 'Payment failed. Please try again.');
            isConfirming = false;
            if (btn) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Confirm & Pay';
            }
        });
    };

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