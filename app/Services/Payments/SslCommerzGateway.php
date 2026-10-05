<?php

namespace App\Services\Payments;

use App\Models\BookedInfo;
use App\Models\OnlinePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SslCommerzGateway extends Gateway
{
    private function base(): string
    {
        return $this->live() ? 'https://securepay.sslcommerz.com' : 'https://sandbox.sslcommerz.com';
    }

    public function initiate(BookedInfo $booking, OnlinePayment $payment, string $returnUrl, string $cancelUrl, string $notifyUrl): array
    {
        $guest = $booking->customer;
        $r = Http::asForm()->post($this->base().'/gwprocess/v4/api.php', [
            'store_id' => $this->config->credential('store_id'),
            'store_passwd' => $this->config->credential('store_password'),
            'total_amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => strtoupper($payment->currency),
            'tran_id' => 'BK'.$payment->id,
            'success_url' => $returnUrl,
            'fail_url' => $cancelUrl,
            'cancel_url' => $cancelUrl,
            'ipn_url' => $notifyUrl,
            'cus_name' => $guest?->full_name ?: 'Guest',
            'cus_email' => $guest?->email ?: 'noreply@example.com',
            'cus_add1' => $guest?->address ?: 'N/A',
            'cus_city' => $guest?->city ?: 'N/A',
            'cus_country' => $guest?->country ?: 'N/A',
            'cus_phone' => $guest?->cust_phone ?: 'N/A',
            'shipping_method' => 'NO',
            'product_name' => 'Booking #'.$booking->booking_number,
            'product_category' => 'Hotel',
            'product_profile' => 'general',
        ]);

        if (! $r->successful() || ($r->json('status') ?? '') !== 'SUCCESS' || ! $r->json('GatewayPageURL')) {
            throw new RuntimeException('SSLCommerz could not start the payment: '.($r->json('failedreason') ?: 'unexpected response'));
        }

        return ['url' => $r->json('GatewayPageURL'), 'reference' => 'BK'.$payment->id];
    }

    public function findReference(Request $request): ?string
    {
        return $request->input('tran_id') ?: null;
    }

    public function verify(OnlinePayment $payment, Request $request): array
    {
        $valId = (string) $request->input('val_id');
        if ($valId === '') {
            return ['paid' => false, 'error' => 'No validation id was supplied.'];
        }

        $r = Http::get($this->base().'/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => $this->config->credential('store_id'),
            'store_passwd' => $this->config->credential('store_password'),
            'format' => 'json',
        ]);
        if (! $r->successful()) {
            return ['paid' => false, 'error' => 'SSLCommerz could not confirm the payment.'];
        }

        $v = $r->json();
        if (($v['tran_id'] ?? null) !== (string) $payment->reference) {
            return ['paid' => false, 'error' => 'The payment does not belong to this booking.'];
        }

        return [
            'paid' => in_array($v['status'] ?? '', ['VALID', 'VALIDATED'], true),
            // SSLCommerz converts to the store currency: check the amount in the currency we asked for.
            'amount' => (float) ($v['currency_amount'] ?? $v['amount'] ?? 0),
            'currency' => strtoupper((string) ($v['currency_type'] ?? $payment->currency)),
            'reference' => $v['bank_tran_id'] ?? $payment->reference,
        ];
    }
}
