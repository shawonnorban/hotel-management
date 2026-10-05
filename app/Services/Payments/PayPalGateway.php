<?php

namespace App\Services\Payments;

use App\Models\BookedInfo;
use App\Models\OnlinePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalGateway extends Gateway
{
    private function base(): string
    {
        return $this->live() ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private function token(): string
    {
        $r = Http::withBasicAuth((string) $this->config->credential('client_id'), (string) $this->config->credential('client_secret'))
            ->asForm()->post($this->base().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $r->successful() || ! $r->json('access_token')) {
            throw new RuntimeException('PayPal rejected the credentials.');
        }

        return $r->json('access_token');
    }

    public function initiate(BookedInfo $booking, OnlinePayment $payment, string $returnUrl, string $cancelUrl, string $notifyUrl): array
    {
        $r = Http::withToken($this->token())->post($this->base().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $payment->id,
                'description' => 'Booking #'.$booking->booking_number,
                'amount' => ['currency_code' => strtoupper($payment->currency), 'value' => number_format((float) $payment->amount, 2, '.', '')],
            ]],
            'application_context' => ['return_url' => $returnUrl, 'cancel_url' => $cancelUrl, 'user_action' => 'PAY_NOW', 'shipping_preference' => 'NO_SHIPPING'],
        ]);

        $approve = collect($r->json('links') ?? [])->firstWhere('rel', 'approve')['href'] ?? null;
        if (! $r->successful() || ! $approve) {
            throw new RuntimeException('PayPal could not start the payment: '.($r->json('message') ?: 'unexpected response'));
        }

        return ['url' => $approve, 'reference' => $r->json('id')];
    }

    public function findReference(Request $request): ?string
    {
        return $request->query('token') ?: null;
    }

    public function verify(OnlinePayment $payment, Request $request): array
    {
        $token = $this->token();
        $r = Http::withToken($token)->withBody('{}', 'application/json')->post($this->base().'/v2/checkout/orders/'.urlencode((string) $payment->reference).'/capture');

        // An already captured order answers 422 ORDER_ALREADY_CAPTURED; read it instead of capturing again.
        if (! $r->successful()) {
            $r = Http::withToken($token)->get($this->base().'/v2/checkout/orders/'.urlencode((string) $payment->reference));
            if (! $r->successful()) {
                return ['paid' => false, 'error' => 'PayPal could not confirm the payment.'];
            }
        }

        $order = $r->json();
        $unit = $order['purchase_units'][0] ?? [];
        if (($unit['reference_id'] ?? null) !== (string) $payment->id) {
            return ['paid' => false, 'error' => 'The payment does not belong to this booking.'];
        }
        $capture = $unit['payments']['captures'][0] ?? [];

        return [
            'paid' => ($order['status'] ?? '') === 'COMPLETED' && ($capture['status'] ?? '') === 'COMPLETED',
            'amount' => (float) ($capture['amount']['value'] ?? 0),
            'currency' => strtoupper((string) ($capture['amount']['currency_code'] ?? '')),
            'reference' => $capture['id'] ?? $payment->reference,
        ];
    }
}
