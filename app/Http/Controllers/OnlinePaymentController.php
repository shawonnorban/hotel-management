<?php

namespace App\Http\Controllers;

use App\Models\OnlinePayment;
use App\Models\PaymentGateway;
use App\Services\OnlinePaymentService;
use App\Services\Payments\StripeGateway;
use Illuminate\Http\Request;

/**
 * Where payment providers send the guest (return / cancel) and where they call us (notify).
 * These routes are public and exempt from CSRF; nothing here is trusted until the provider's API confirms it.
 */
class OnlinePaymentController extends Controller
{
    public function __construct(private OnlinePaymentService $online) {}

    public function return(Request $request, string $driver)
    {
        $payment = $this->online->locate($driver, $request);
        if (! $payment) {
            return response()->view('payments.result', ['payment' => null, 'ok' => false, 'message' => 'We could not find that payment.'], 404);
        }

        $payment = $this->online->complete($payment, $request);

        return view('payments.result', [
            'payment' => $payment,
            'booking' => $payment->booking,
            'ok' => $payment->status === 'paid' && ! $payment->failure,
            'message' => $payment->status === 'paid'
                ? ($payment->failure ? 'Your payment was received but needs attention from our team. We will contact you.' : 'Thank you! Your payment was successful.')
                : ($payment->failure ?: 'The payment was not completed.'),
        ]);
    }

    public function cancel(Request $request, string $driver)
    {
        $payment = $this->online->locate($driver, $request);
        if ($payment) {
            $this->online->cancel($payment);
        }

        return view('payments.result', [
            'payment' => $payment,
            'booking' => $payment?->booking,
            'ok' => false,
            'message' => 'The payment was cancelled. You have not been charged.',
        ]);
    }

    /** Server-to-server notification (Stripe webhook, SSLCommerz IPN). */
    public function notify(Request $request, string $driver)
    {
        $gateway = PaymentGateway::where('driver', $driver)->first();
        if (! $gateway) {
            return response('', 404);
        }

        if ($driver === 'stripe') {
            /** @var StripeGateway $stripe */
            $stripe = $this->online->driver($gateway);
            if (! $stripe->validWebhook($request->getContent(), $request->header('Stripe-Signature'))) {
                return response('Invalid signature', 400);
            }

            $event = json_decode($request->getContent(), true) ?: [];
            if (($event['type'] ?? '') === 'checkout.session.completed') {
                $sessionId = $event['data']['object']['id'] ?? null;
                $payment = $sessionId ? OnlinePayment::where('driver', 'stripe')->where('reference', $sessionId)->first() : null;
                if ($payment) {
                    $this->online->complete($payment, $request);
                }
            }

            return response('ok');
        }

        $payment = $this->online->locate($driver, $request);
        if ($payment) {
            $this->online->complete($payment, $request);
        }

        return response('ok');
    }
}
