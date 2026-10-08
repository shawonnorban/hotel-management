@extends('layouts.admin')
@section('title', 'Advance bookings')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3"><div class="me-auto"><h1 class="page-title">Advance bookings</h1><p class="page-sub">Upcoming reservations and the advance received for each.</p></div>
    @can('reservations.create')<a href="{{ route('admin.reservations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New reservation</a>@endcan</div>
@error('advance')<div class="alert alert-danger">{{ $message }}</div>@enderror
@can('settings.manage')
<form method="post" action="{{ route('admin.advance.rule') }}" class="card mb-4"><div class="card-body row g-3 align-items-end">@csrf @method('PUT')
    <div class="col-md-3"><label class="form-label small fw-semibold">Required advance (% of total)</label><input type="number" step="0.01" min="0" max="100" name="percent" class="form-control" value="{{ old('percent', $percent) }}"></div>
    <div class="col-md-3"><label class="form-label small fw-semibold">Release unpaid after (days)</label><input type="number" min="0" max="365" name="hold_days" class="form-control" value="{{ old('hold_days', $holdDays) }}"></div>
    <div class="col-md-4 small text-body-secondary">0% keeps today's behaviour: staff bookings are confirmed at once. With a percentage, a booking stays <em>pending</em> until that share is paid, and pending bookings with no payment are cancelled after the hold period.</div>
    <div class="col-auto ms-auto"><button class="btn btn-outline-primary">Save rule</button></div>
</div></form>
@endcan
<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input name="q" class="form-control" placeholder="Booking, guest or phone" value="{{ $f['q'] ?? '' }}"></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="Arrival from" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="Arrival to" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-auto form-check pt-2"><input type="checkbox" class="form-check-input" name="due" value="1" id="due" @checked(request()->boolean('due'))><label for="due" class="form-check-label">Advance still due</label></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Booking</th><th>Guest</th><th>Arrival</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Advance paid</th><th class="text-end">Required</th><th class="text-end">Balance</th><th></th></tr></thead><tbody>
    @forelse ($rows as $r)
        @php $b = $r['booking']; @endphp
        <tr><td><a class="fw-semibold text-decoration-none" href="{{ route('admin.reservations.show', $b->booking_number) }}">#{{ $b->booking_number }}</a></td>
            <td>{{ $b->full_guest_name ?: $b->customer?->full_name }}<div class="small text-body-secondary">{{ $b->customer?->cust_phone }}</div></td>
            <td class="text-nowrap">{{ $b->checkindate->format('d M Y') }}<div class="small text-body-secondary">{{ $r['days'] === 0 ? 'today' : 'in '.$r['days'].' day(s)' }}</div></td>
            <td><span class="badge text-bg-{{ (string) $b->bookingstatus === '0' ? 'warning' : 'primary' }}">{{ $b->status_label }}</span></td>
            <td class="text-end">{{ \App\Support\Money::format($b->total_price) }}</td><td class="text-end">{{ \App\Support\Money::format($b->paid_amount) }}</td>
            <td class="text-end {{ $r['shortfall'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ $r['required'] > 0 ? \App\Support\Money::format($r['required']) : '—' }}</td>
            <td class="text-end">{{ \App\Support\Money::format($b->balance) }}</td>
            <td class="text-end">@can('reservations.payments')@if ($b->balance > 0)<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#advModal" data-action="{{ route('admin.advance.receive', $b->booking_number) }}" data-amount="{{ $r['shortfall'] > 0 ? $r['shortfall'] : '' }}" data-title="#{{ $b->booking_number }}">Receive advance</button>@endif @endcan</td></tr>
    @empty
        <tr><td colspan="9"><div class="empty"><i class="bi bi-calendar-plus"></i>No upcoming reservations.</div></td></tr>
    @endforelse
    </tbody>
    @if ($rows->isNotEmpty())<tfoot><tr class="fw-semibold"><td colspan="5">Total</td><td class="text-end">{{ \App\Support\Money::format($totals['paid']) }}</td><td></td><td class="text-end">{{ \App\Support\Money::format($totals['balance']) }}</td><td></td></tr></tfoot>@endif
</table></div></div>
@can('reservations.payments')
<div class="modal fade" id="advModal" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content" id="advForm">@csrf
    <div class="modal-header"><h5 class="modal-title">Receive advance <span id="advTitle"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">Amount</label><input type="number" step="0.01" min="0.01" name="amount" id="advAmount" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Payment mode</label><select name="method" class="form-select" required>@foreach ($methods as $m)<option value="{{ $m->payment_method_id }}">{{ $m->payment_method }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label small fw-semibold">Reference</label><input name="reference" class="form-control" maxlength="60"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Record</button></div></form></div></div>
<script>document.getElementById('advModal').addEventListener('show.bs.modal',function(e){var b=e.relatedTarget;document.getElementById('advForm').action=b.dataset.action;document.getElementById('advAmount').value=b.dataset.amount||'';document.getElementById('advTitle').textContent=b.dataset.title;});</script>
@endcan
@endsection
