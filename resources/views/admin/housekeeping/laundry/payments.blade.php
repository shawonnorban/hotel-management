@extends('layouts.admin')
@section('title', 'Laundry payment records')
@section('content')
<div class="mb-4"><h1 class="page-title">Payment records</h1><p class="page-sub">Laundry payments received · total <strong>{{ \App\Support\Money::format($total) }}</strong></p></div>
<form method="get" class="row g-2 mb-3"><div class="col-md-2"><input name="from" class="form-control" data-date placeholder="From" value="{{ $f['from'] ?? '' }}"></div><div class="col-md-2"><input name="to" class="form-control" data-date placeholder="To" value="{{ $f['to'] ?? '' }}"></div><div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div></form>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Date</th><th>Order</th><th>Guest</th><th>Received in</th><th>Reference</th><th class="text-end">Amount</th></tr></thead><tbody>
    @forelse ($payments as $p)<tr><td>{{ $p->paid_on->format('d M Y') }}</td><td><a class="text-decoration-none" href="{{ route('admin.laundry.show', $p->order) }}">{{ $p->order->number }}</a></td><td>{{ $p->order->guest_name }}</td><td>{{ $p->account->name }}</td><td>{{ $p->reference }}</td><td class="text-end">{{ \App\Support\Money::format($p->amount) }}</td></tr>
    @empty<tr><td colspan="6"><div class="empty"><i class="bi bi-receipt"></i>No payments yet.</div></td></tr>@endforelse
    </tbody></table></div>
    @if ($payments->hasPages())<div class="card-footer bg-transparent">{{ $payments->links() }}</div>@endif
</div>
@endsection
