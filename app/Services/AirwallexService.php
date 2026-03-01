<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AirwallexService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $apiKey;

    public function __construct()
    {
        $env    = config('services.airwallex.env', 'sandbox');
        $config = config("services.airwallex.$env");

        $this->baseUrl  = rtrim($config['base_url'], '/');
        $this->clientId = $config['client_id'];
        $this->apiKey   = $config['api_key'];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Authentication
    // ─────────────────────────────────────────────────────────────────────

    protected function getToken(): string
    {
        return Cache::remember('airwallex_bearer_token', 1680, function () {

            $response = Http::withHeaders([
                'x-client-id'   => $this->clientId,
                'x-api-key'     => $this->apiKey,
                'x-api-version' => '2020-09-22',
                'Content-Type'  => 'application/json',
            ])->post($this->baseUrl . '/api/v1/authentication/login');

            Log::info('Airwallex Auth Response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (!$response->successful()) {
                throw new \Exception(
                    'Airwallex authentication failed [' . $response->status() . ']: ' . $response->body()
                );
            }

            $token = $response->json('token');

            if (empty($token)) {
                throw new \Exception('Airwallex returned empty token. Check client_id and api_key.');
            }

            return $token;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Create Payment Intent
    // ─────────────────────────────────────────────────────────────────────

    public function createPaymentIntent(float $amount, string $orderId, ?string $customerId = null): array
    {
        $token = $this->getToken();

        $payload = [
            'request_id'        => uniqid('req_', true),
            'amount'            => round($amount, 2),
            'currency'          => 'USD',
            'merchant_order_id' => (string) $orderId,
        ];

        if ($customerId) {
            $payload['customer_id'] = $customerId;
        }

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
            'Content-Type'  => 'application/json',
        ])
            ->withToken($token)
            ->timeout(20)
            ->post($this->baseUrl . '/api/v1/pa/payment_intents/create', $payload);

        if (!$response->successful()) {
            Log::error('Airwallex createPaymentIntent failed', [
                'order_id' => $orderId,
                'payload'  => $payload,
                'response' => $response->json(),
            ]);
            throw new \Exception('Payment initialization failed: ' . $response->body());
        }

        Log::info('[Airwallex] PaymentIntent created', [
            'order_id'  => $orderId,
            'intent_id' => $response->json('id'),
            'amount'    => $amount,
        ]);

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Get Payment Intent
    // ─────────────────────────────────────────────────────────────────────

    public function getPaymentIntent(string $intentId): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
        ])
            ->withToken($token)
            ->timeout(15)
            ->get($this->baseUrl . '/api/v1/pa/payment_intents/' . $intentId);

        if (!$response->successful()) {
            Log::error('Airwallex getPaymentIntent failed', [
                'intent_id' => $intentId,
                'response'  => $response->json(),
            ]);
            throw new \Exception('Unable to verify payment: ' . $response->body());
        }

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Create Customer
    // ─────────────────────────────────────────────────────────────────────

    public function createCustomer(
        string $userId,
        string $email,
        string $firstName = '',
        string $lastName  = ''
    ): array {

        $token = $this->getToken();

        $payload = [
            'request_id'           => uniqid('cus_', true),
            'merchant_customer_id' => 'user_' . $userId,
            'email'                => $email,
            'first_name'           => $firstName,
            'last_name'            => $lastName,
        ];

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
            'Content-Type'  => 'application/json',
        ])
            ->withToken($token)
            ->post($this->baseUrl . '/api/v1/pa/customers/create', $payload);

        if (!$response->successful()) {
            throw new \Exception('Customer create failed: ' . $response->body());
        }

        Log::info('[Airwallex] Customer created', ['user_id' => $userId]);

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ✅ NEW: Ensure Customer
    //  Check if user already has airwallex_customer_id — create if not
    // ─────────────────────────────────────────────────────────────────────

    public function ensureCustomer($user): string
    {
        // Already has customer ID — return it
        if (!empty($user->airwallex_customer_id)) {
            return $user->airwallex_customer_id;
        }

        // Create new customer
        $customer = $this->createCustomer(
            userId:    (string) $user->id,
            email:     $user->email ?? '',
            firstName: $user->fname ?? $user->name ?? '',
            lastName:  $user->lname ?? '',
        );

        $customerId = $customer['id'] ?? null;

        if (!$customerId) {
            throw new \Exception('Airwallex customer creation returned no ID');
        }

        // Save to users table
        DB::table('users')
            ->where('id', $user->id)
            ->update(['airwallex_customer_id' => $customerId]);

        Log::info('[Airwallex] Customer ensured', [
            'user_id'     => $user->id,
            'customer_id' => $customerId,
        ]);

        return $customerId;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ✅ NEW: Create Payment Consent (for saving card)
    // ─────────────────────────────────────────────────────────────────────

    public function createPaymentConsent(string $customerId, string $triggeredBy = 'customer'): array
    {
        $token = $this->getToken();

        $payload = [
            'request_id'   => uniqid('cst_', true),
            'customer_id'  => $customerId,
            'merchant_trigger_reason' => $triggeredBy === 'customer' ? 'scheduled' : 'unscheduled',
            'next_triggered_by' => $triggeredBy,
        ];

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
            'Content-Type'  => 'application/json',
        ])
            ->withToken($token)
            ->post($this->baseUrl . '/api/v1/pa/payment_consents/create', $payload);

        if (!$response->successful()) {
            Log::error('[Airwallex] createPaymentConsent failed', [
                'customer_id' => $customerId,
                'response'    => $response->json(),
            ]);
            throw new \Exception('PaymentConsent creation failed: ' . $response->body());
        }

        Log::info('[Airwallex] PaymentConsent created', [
            'consent_id' => $response->json('id'),
        ]);

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ✅ NEW: Get Payment Consent (fetch card details after verification)
    // ─────────────────────────────────────────────────────────────────────

    public function getPaymentConsent(string $consentId): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
        ])
            ->withToken($token)
            ->timeout(15)
            ->get($this->baseUrl . '/api/v1/pa/payment_consents/' . $consentId);

        if (!$response->successful()) {
            Log::error('[Airwallex] getPaymentConsent failed', [
                'consent_id' => $consentId,
                'response'   => $response->json(),
            ]);
            throw new \Exception('Unable to fetch payment consent: ' . $response->body());
        }

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ✅ NEW: Disable Payment Consent (delete saved card)
    // ─────────────────────────────────────────────────────────────────────

    public function disablePaymentConsent(string $consentId): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
            'Content-Type'  => 'application/json',
        ])
            ->withToken($token)
            ->post($this->baseUrl . '/api/v1/pa/payment_consents/' . $consentId . '/disable', [
                'request_id' => uniqid('dis_', true),
            ]);

        if (!$response->successful()) {
            Log::error('[Airwallex] disablePaymentConsent failed', [
                'consent_id' => $consentId,
                'response'   => $response->json(),
            ]);
            throw new \Exception('Unable to disable payment consent: ' . $response->body());
        }

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ✅ NEW: Confirm Intent With Consent (MIT — saved card payment)
    // ─────────────────────────────────────────────────────────────────────

    public function confirmIntentWithConsent(string $intentId, string $consentId): array
    {
        $token = $this->getToken();

        $payload = [
            'request_id'        => uniqid('mit_', true),
            'payment_consent_id' => $consentId,
            'triggered_by'      => 'merchant',
            'payment_method_options' => [
                'card' => [
                    'authorization_type' => 'final_auth',
                    'auto_capture'       => true,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
            'Content-Type'  => 'application/json',
        ])
            ->withToken($token)
            ->post($this->baseUrl . "/api/v1/pa/payment_intents/{$intentId}/confirm_with_customer_consent", $payload);

        if (!$response->successful()) {
            Log::error('[Airwallex] confirmIntentWithConsent failed', [
                'intent_id'  => $intentId,
                'consent_id' => $consentId,
                'response'   => $response->json(),
            ]);
            throw new \Exception('Saved card payment confirmation failed: ' . $response->body());
        }

        Log::info('[Airwallex] Intent confirmed with consent', [
            'intent_id'  => $intentId,
            'consent_id' => $consentId,
            'status'     => $response->json('status'),
        ]);

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Confirm Payment Intent With Saved Card (CIT)
    // ─────────────────────────────────────────────────────────────────────

    public function confirmPaymentIntentWithSavedCard(
        string  $intentId,
        string  $customerId,
        string  $paymentMethodId,
        string  $triggeredBy = 'customer',
        ?string $cvc         = null
    ): array {

        $token = $this->getToken();

        $paymentMethod = [
            'id'   => $paymentMethodId,
            'type' => 'card',
        ];

        if ($triggeredBy === 'customer' && $cvc) {
            $paymentMethod['card'] = ['cvc' => $cvc];
        }

        $payload = [
            'request_id'     => uniqid('cnf_', true),
            'customer_id'    => $customerId,
            'payment_method' => $paymentMethod,
            'triggered_by'   => $triggeredBy,
            'payment_method_options' => [
                'card' => [
                    'authorization_type' => 'final_auth',
                    'auto_capture'       => true,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
            'Content-Type'  => 'application/json',
        ])
            ->withToken($token)
            ->post($this->baseUrl . "/api/v1/pa/payment_intents/{$intentId}/confirm", $payload);

        if (!$response->successful()) {
            Log::error('[Airwallex] confirmPaymentIntentWithSavedCard failed', [
                'response' => $response->json(),
            ]);
            throw new \Exception('Saved card payment failed');
        }

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Webhook Signature Verification
    // ─────────────────────────────────────────────────────────────────────

    public function verifyWebhookSignature(
        string $rawBody,
        string $signature,
        string $timestamp
    ): bool {
        $secret = config('services.airwallex.webhook_secret', '');

        if (empty($secret)) {
            Log::warning('Airwallex webhook_secret not set — skipping signature check');
            return true;
        }

        $expected = hash_hmac('sha256', $timestamp . $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    // Force-clear cached token
    public function clearTokenCache(): void
    {
        Cache::forget('airwallex_bearer_token');
    }
}