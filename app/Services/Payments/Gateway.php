<?php

namespace App\Services\Payments;

use App\Models\BookedInfo;
use App\Models\OnlinePayment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

/** A hosted-checkout payment provider. */
abstract class Gateway
{
    public function __construct(protected PaymentGateway $config) {}

    /**
     * Create the payment on the provider's side.
     *
     * @return array{url:string,reference:string} where to send the guest, and the provider's reference for it
     */
    abstract public function initiate(BookedInfo $booking, OnlinePayment $payment, string $returnUrl, string $cancelUrl, string $notifyUrl): array;

    /**
     * Ask the provider (never the browser) whether the payment really went through.
     *
     * @return array{paid:bool,amount?:float,currency?:string,reference?:string,error?:string}
     */
    abstract public function verify(OnlinePayment $payment, Request $request): array;

    /** Provider-specific lookup of our payment from the callback's parameters. */
    abstract public function findReference(Request $request): ?string;

    protected function live(): bool
    {
        return (bool) $this->config->live;
    }
}
