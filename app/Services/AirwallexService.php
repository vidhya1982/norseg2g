<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirwallexService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $apiKey;

    public function __construct()
    {
        // ✅ Match your actual config path: config/services.php → airwallex.sandbox
        $env    = config('services.airwallex.env', 'sandbox');
        $config = config("services.airwallex.$env");

        $this->baseUrl  = rtrim($config['base_url'], '/');
        $this->clientId = $config['client_id'];
        $this->apiKey   = $config['api_key'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Authentication
    //  ✅ FIX: credentials go in HEADERS (x-client-id / x-api-key), NOT body
    //  Airwallex docs: POST /api/v1/authentication/login
    // ─────────────────────────────────────────────────────────────────────────

    protected function getToken(): string
    {
        // Cache token for 28 min (Airwallex tokens last ~30 min)
        return Cache::remember('airwallex_bearer_token', 1680, function () {

            $response = Http::withHeaders([
                'x-client-id'    => $this->clientId,   // ✅ header, not body
                'x-api-key'      => $this->apiKey,      // ✅ header, not body
                'x-api-version'  => '2020-09-22',
                'Content-Type'   => 'application/json',
            ])->post($this->baseUrl . '/api/v1/authentication/login');
            // ✅ No body payload at all — Airwallex reads credentials from headers only

            Log::info('Airwallex Auth Response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (! $response->successful()) {
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

    // ─────────────────────────────────────────────────────────────────────────
    //  Create Payment Intent
    //  ✅ FIX: amount stays in DOLLARS (e.g. 15.00), NOT cents
    //  Airwallex USD uses decimal amounts — do NOT multiply by 100
    // ─────────────────────────────────────────────────────────────────────────

    public function createPaymentIntent(float $amount, string $orderId): array
    {
        $token = $this->getToken();

        $payload = [
            'request_id'        => uniqid('req_', true),
            'amount'            => round($amount, 2),   // ✅ dollars as-is: 15.00
            'currency'          => 'USD',
            'merchant_order_id' => (string) $orderId,
        ];

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
            'Content-Type'  => 'application/json',
        ])
            ->withToken($token)
            ->timeout(20)
            ->post($this->baseUrl . '/api/v1/pa/payment_intents/create', $payload);

        if (! $response->successful()) {
            Log::error('Airwallex createPaymentIntent failed', [
                'order_id' => $orderId,
                'payload'  => $payload,
                'response' => $response->json(),
            ]);
            throw new \Exception('Payment initialization failed: ' . $response->body());
        }

        Log::info('Airwallex PaymentIntent Created', [
            'order_id'  => $orderId,
            'intent_id' => $response->json('id'),
            'amount'    => $amount,
        ]);

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Get / Verify Payment Intent
    // ─────────────────────────────────────────────────────────────────────────

    public function getPaymentIntent(string $intentId): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'x-api-version' => '2020-09-22',
        ])
            ->withToken($token)
            ->timeout(15)
            ->get($this->baseUrl . '/api/v1/pa/payment_intents/' . $intentId);

        if (! $response->successful()) {
            Log::error('Airwallex getPaymentIntent failed', [
                'intent_id' => $intentId,
                'response'  => $response->json(),
            ]);
            throw new \Exception('Unable to verify payment: ' . $response->body());
        }

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Webhook Signature Verification
    // ─────────────────────────────────────────────────────────────────────────

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

    // Force-clear cached token (use if you rotate API keys)
    public function clearTokenCache(): void
    {
        Cache::forget('airwallex_bearer_token');
    }
}