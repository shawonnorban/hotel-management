<?php

namespace App\Services;

use App\Models\BookedInfo;
use App\Models\OnlinePayment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Services\Payments\Gateway;
use App\Services\Payments\PayPalGateway;
use App\Services\Payments\SslCommerzGateway;
use App\Services\Payments\StripeGateway;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Online card/wallet payments. A guest is sent to the provider's hosted page; when they come back (or the provider
 * calls us) the payment is verified with the provider's API before any money is recorded.
 */
class OnlinePaymentService
{
    private const CLASSES = ['stripe' => StripeGateway::class, 'paypal' => PayPalGateway::class, 'sslcommerz' => SslCommerzGateway::class];

    public function __construct(private PaymentService $payments, private ReservationLog $log, private BookingNotifier $notifier)
    {
    }

    /** The gateway behind a payment method, when it is switched on and fully configured. */
    public function gatewayForMethod(PaymentMethod $method): ?PaymentGateway
    {
        if (! $method->is_active) {
            return null;
        }
        $gateway = PaymentGateway::where('payment_method_id', $method->payment_method_id)->first();

        return $gateway && $gateway->isConfigured() ? $gateway : null;
    }

    public function driver(PaymentGateway|string $gateway): Gateway
    {
        $gateway = $gateway instanceof PaymentGateway ? $gateway : PaymentGateway::where('driver', $gateway)->firstOrFail();
        $class = self::CLASSES[$gateway->driver] ?? throw new InvalidArgumentException('Unknown gateway.');

        return new $class($gateway);
    }

    /** Begin a payment of the booking's balance; returns the provider URL to send the guest to. */
    public function start(BookedInfo $booking, PaymentGateway $gateway): string
    {
        if ($booking->balance <= 0.004 || ! $booking->is_open) {
            throw new InvalidArgumentException('There is nothing to pay on this booking.');
        }

        $payment = OnlinePayment::create([
            'bookedid' => $booking->bookedid,
            'driver' => $gateway->driver,
            'amount' => $booking->balance,
            'currency' => strtoupper($gateway->currency),
            'status' => 'pending',
        ]);

        try {
            $result = $this->driver($gateway)->initiate(
                $booking, $payment,
                route('payments.return', ['driver' => $gateway->driver, 'payment' => $payment->id]),
                route('payments.cancel', ['driver' => $gateway->driver, 'payment' => $payment->id]),
                route('payments.notify', $gateway->driver),
            );
        } catch (Throwable $e) {
            $payment->update(['status' => 'failed', 'failure' => mb_substr($e->getMessage(), 0, 250)]);
            report($e);

            throw new RuntimeException('The payment provider is not available right now. Please try again or choose another method.');
        }

        $payment->update(['reference' => $result['reference']]);

        return $result['url'];
    }

    /** Find the pending payment a provider callback refers to. */
    public function locate(string $driver, Request $request): ?OnlinePayment
    {
        $gateway = PaymentGateway::where('driver', $driver)->first();
        if (! $gateway) {
            return null;
        }
        $reference = $this->driver($gateway)->findReference($request);
        $byReference = $reference ? OnlinePayment::where('driver', $driver)->where('reference', $reference)->first() : null;

        // Cancel pages carry only our own payment id.
        return $byReference ?? ($request->query('payment') ? OnlinePayment::where('driver', $driver)->find($request->query('payment')) : null);
    }

    /** Verify with the provider and, if paid, record the money. Safe to call repeatedly. */
    public function complete(OnlinePayment $payment, Request $request): OnlinePayment
    {
        return DB::transaction(function () use ($payment, $request) {
            $payment = OnlinePayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'paid') {
                return $payment;
            }

            $gateway = PaymentGateway::where('driver', $payment->driver)->firstOrFail();
            try {
                $result = $this->driver($gateway)->verify($payment, $request);
            } catch (Throwable $e) {
                report($e);
                $result = ['paid' => false, 'error' => 'The provider could not be reached.'];
            }

            if (! ($result['paid'] ?? false)) {
                $payment->update(['status' => 'failed', 'failure' => mb_substr($result['error'] ?? 'The payment was not completed.', 0, 250)]);

                return $payment;
            }

            if (abs(($result['amount'] ?? 0) - (float) $payment->amount) > 0.01 || strtoupper($result['currency'] ?? '') !== strtoupper($payment->currency)) {
                $payment->update(['status' => 'failed', 'failure' => 'The amount or currency did not match what was requested.']);

                return $payment;
            }

            $booking = BookedInfo::findOrFail($payment->bookedid);
            $method = $gateway->method ?? PaymentMethod::findOrFail($gateway->payment_method_id);

            try {
                $guestPayment = $this->payments->receive($booking, (float) $payment->amount, $method, null, 'Online payment', $result['reference'] ?? $payment->reference);
                if ((string) $booking->bookingstatus === '0') {
                    $booking->update(['bookingstatus' => '2']);
                    $this->log->add($booking, 'confirmed', 'Confirmed by online payment');
                }
                $payment->update(['status' => 'paid', 'paid_at' => now(), 'guest_payment_id' => $guestPayment->payid, 'failure' => null]);
            } catch (InvalidArgumentException $e) {
                // The provider took the money but the booking can no longer accept it (e.g. cancelled meanwhile).
                $payment->update(['status' => 'paid', 'paid_at' => now(), 'failure' => 'Not applied to the booking – refund required: '.$e->getMessage()]);
                $this->log->add($booking, 'payment_problem', 'Online payment received but not applied: '.$e->getMessage());

                return $payment;
            }

            DB::afterCommit(fn () => $this->notifier->paid($booking->fresh(), (float) $payment->amount));

            return $payment;
        });
    }

    public function cancel(OnlinePayment $payment): OnlinePayment
    {
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'cancelled']);
        }

        return $payment;
    }

    /** Default ISO currency for new gateways: the hotel's currency code when it is a 3-letter code. */
    public static function defaultCurrency(): string
    {
        $code = strtoupper((string) \App\Support\Money::currency()?->currencyname);

        return preg_match('/^[A-Z]{3}$/', $code) ? $code : 'USD';
    }
}
