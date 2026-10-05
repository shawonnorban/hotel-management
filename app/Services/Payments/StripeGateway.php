<?php

namespace App\Services\Payments;

use App\Models\BookedInfo;
use App\Models\OnlinePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGateway extends Gateway
{
    private const API = 'https://api.stripe.com/v1';

    public function initiate(BookedInfo $booking, OnlinePayment $payment, string $returnUrl, string $cancelUrl, string $notifyUrl): array
    {
        $response = Http::withBasicAuth($this->config->credential('secret_key'), '')->asForm()->post(self::API.'/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $payment->id,
            'customer_email' => $booking->customer?->email ?: null,
            'metadata[booking]' => $booking->booking_number,
            'metadata[payment]' => (string) $payment->id,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($payment->currency),
            'line_items[0][price_data][unit_amount]' => (int) round((float) $payment->amount * 100),
            'line_items[0][price_data][product_data][name]' => 'Booking #'.$booking->booking_number,
        ]);

        if (! $response->successful() || ! $response->json('url')) {
            throw new RuntimeException('Stripe could not start the payment: '.($response->json('error.message') ?: 'unexpected response'));
        }

        return ['url' => $response->json('url'), 'reference' => $response->json('id')];
    }

    public function findReference(Request $request): ?string
    {
        return $request->query('session_id') ?: null;
    }

    public function verify(OnlinePayment $payment, Request $request): array
    {
        $response = Http::withBasicAuth($this->config->credential('secret_key'), '')->get(self::API.'/checkout/sessions/'.urlencode((string) $payment->reference));
        if (! $response->successful()) {
            return ['paid' => false, 'error' => 'Stripe could not confirm the payment.'];
        }

        $s = $response->json();
        if (($s['metadata']['payment'] ?? null) !== (string) $payment->id) {
            return ['paid' => false, 'error' => 'The payment does not belong to this booking.'];
        }

        return [
            'paid' => ($s['payment_status'] ?? '') === 'paid',
            'amount' => ((int) ($s['amount_total'] ?? 0)) / 100,
            'currency' => strtoupper((string) ($s['currency'] ?? '')),
            'reference' => $s['id'] ?? $payment->reference,
        ];
    }

    /** Verify a webhook delivery's signature (Stripe-Signature: t=…,v1=…) with a 5 minute tolerance. */
    public function validWebhook(string $payload, ?string $header): bool
    {
        $secret = $this->config->credential('webhook_secret');
        if (! $secret || ! $header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', $piece, 2), 2, '');
            $parts[$k][] = $v;
        }
        $timestamp = (int) ($parts['t'][0] ?? 0);
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($parts['v1'] ?? [] as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
