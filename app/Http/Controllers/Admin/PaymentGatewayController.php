<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Services\OnlinePaymentService;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        foreach (PaymentGateway::DRIVERS as $driver => $meta) {
            PaymentGateway::firstOrCreate(['driver' => $driver], ['payment_method_id' => $meta['method'], 'currency' => OnlinePaymentService::defaultCurrency()]);
        }

        return view('admin.settings.gateways', [
            'gateways' => PaymentGateway::orderBy('driver')->get(),
            'methods' => PaymentMethod::orderBy('payment_method')->pluck('payment_method', 'payment_method_id'),
        ]);
    }

    public function update(Request $request, string $driver)
    {
        $meta = PaymentGateway::DRIVERS[$driver] ?? abort(404);
        $gateway = PaymentGateway::where('driver', $driver)->firstOrFail();

        $rules = [
            'payment_method_id' => ['required', 'integer', 'exists:payment_method,payment_method_id'],
            'currency' => ['required', 'alpha', 'size:3'],
            'live' => ['boolean'],
        ];
        foreach (array_keys($meta['fields']) as $field) {
            $rules['credentials.'.$field] = ['nullable', 'string', 'max:300'];
        }
        $data = $request->validate($rules);

        // A blank secret field keeps the stored value, so secrets never have to be shown again.
        $credentials = $gateway->credentials ?? [];
        foreach (array_keys($meta['fields']) as $field) {
            $value = trim((string) ($data['credentials'][$field] ?? ''));
            if ($value !== '') {
                $credentials[$field] = $value;
            }
        }
        if ($request->boolean('clear')) {
            $credentials = [];
        }

        $gateway->update([
            'payment_method_id' => $data['payment_method_id'],
            'currency' => strtoupper($data['currency']),
            'live' => $request->boolean('live'),
            'credentials' => $credentials,
        ]);

        return back()->with('status', $meta['label'].' settings saved.');
    }
}
