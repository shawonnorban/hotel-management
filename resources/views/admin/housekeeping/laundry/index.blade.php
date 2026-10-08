@extends('layouts.admin')
@section('title', 'Laundry list')
@section('content')
<div class="d-flex align-items-center mb-3"><div class="me-auto"><h1 class="page-title">Laundry list</h1><p class="page-sub">Guest laundry orders.</p></div>
    @can('hk-laundry.manage')<a href="{{ route('admin.laundry.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New order</a>@endcan</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Order, guest or room" value="{{ $f['q'] ?? '' }}"></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (\App\Models\HkLaundryOrder::STATUSES as $k => $l)<option value="{{ $k }}" @selected(($f['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-auto form-check pt-2"><input type="checkbox" class="form-check-input" name="unpaid" value="1" id="unp" @checked(request()->boolean('unpaid'))><label for="unp" class="form-check-label">Unpaid only</label></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Order</th><th>Date</th><th>Guest</th><th>Room</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Due</th></tr></thead><tbody>
    @forelse ($orders as $o)
        <tr><td><a class="fw-semibold text-decoration-none" href="{{ route('admin.laundry.show', $o) }}">{{ $o->number }}</a></td><td>{{ $o->order_date->format('d M Y') }}</td><td>{{ $o->guest_name }}</td><td>{{ $o->room_no }}</td>
            <td><span class="badge text-bg-{{ ['received' => 'warning', 'washing' => 'primary', 'ready' => 'info', 'delivered' => 'success', 'cancelled' => 'secondary'][$o->status] }}">{{ \App\Models\HkLaundryOrder::STATUSES[$o->status] }}</span></td>
            <td class="text-end">{{ \App\Support\Money::format($o->total) }}</td><td class="text-end {{ $o->status !== 'cancelled' && $o->due > 0 ? 'text-danger fw-semibold' : 'text-body-secondary' }}">{{ \App\Support\Money::format($o->status === 'cancelled' ? 0 : $o->due) }}</td></tr>
    @empty
        <tr><td colspan="7"><div class="empty"><i class="bi bi-minecart-loaded"></i>No laundry orders yet.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($orders->hasPages())<div class="card-footer bg-transparent">{{ $orders->links() }}</div>@endif
</div>
@endsection
