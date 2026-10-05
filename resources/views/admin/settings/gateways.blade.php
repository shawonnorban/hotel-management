@extends('layouts.admin')
@section('title', 'Payment gateways')
@section('content')
<div class="mb-4"><h1 class="page-title">Payment gateways</h1><p class="page-sub">Let guests pay online. Secrets are stored encrypted and are never shown again after saving.</p></div>
<div class="row g-4">
@foreach ($gateways as $gateway)
    @php($meta = \App\Models\PaymentGateway::DRIVERS[$gateway->driver])
    <div class="col-xl-4 col-lg-6"><form method="post" action="{{ route('admin.gateways.update', $gateway->driver) }}" class="card h-100">
        @csrf @method('PUT')
        <div class="card-header d-flex align-items-center">{{ $meta['label'] }}
            <span class="badge ms-auto text-bg-{{ $gateway->isConfigured() ? 'success' : 'secondary' }}">{{ $gateway->isConfigured() ? 'Configured' : 'Not configured' }}</span></div>
        <div class="card-body row g-3">
            @foreach ($meta['fields'] as $key => $label)
                <div class="col-12"><label class="form-label small fw-semibold">{{ $label }}</label>
                    <input type="password" name="credentials[{{ $key }}]" class="form-control" autocomplete="off" placeholder="{{ $gateway->credential($key) ? '•••••••• (saved — leave blank to keep)' : '' }}"></div>
            @endforeach
            <div class="col-6"><label class="form-label small fw-semibold">Currency</label><input name="currency" class="form-control text-uppercase" maxlength="3" value="{{ old('currency', $gateway->currency) }}" required></div>
            <div class="col-6"><label class="form-label small fw-semibold">Payment method</label>
                <select name="payment_method_id" class="form-select">@foreach ($methods as $id => $name)<option value="{{ $id }}" @selected((int) $gateway->payment_method_id === (int) $id)>{{ $name }}</option>@endforeach</select></div>
            <div class="col-12 form-check form-switch ms-2"><input class="form-check-input" type="checkbox" role="switch" name="live" value="1" id="live{{ $gateway->driver }}" @checked($gateway->live)><label class="form-check-label" for="live{{ $gateway->driver }}">Live mode (off = sandbox / test)</label></div>
            <div class="col-12 small text-body-secondary">
                @if ($gateway->driver === 'stripe')Webhook URL: <code>{{ route('payments.notify', 'stripe') }}</code> (event <em>checkout.session.completed</em>).
                @elseif ($gateway->driver === 'sslcommerz')IPN URL: <code>{{ route('payments.notify', 'sslcommerz') }}</code>.
                @else Guests return to the site after approving; no webhook is required.@endif
                <br>The payment method must also be switched on under <a href="{{ route('admin.resource.index', 'payment-methods') }}">Payment methods</a>.
            </div>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-between"><button name="clear" value="1" class="btn btn-sm btn-outline-danger" data-allow-multi formnovalidate onclick="return confirm('Remove the saved credentials?')">Clear credentials</button><button class="btn btn-primary btn-sm px-3">Save</button></div>
    </form></div>
@endforeach
</div>
@endsection
