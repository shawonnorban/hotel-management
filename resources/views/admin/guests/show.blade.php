@extends('layouts.admin')
@section('title', $customer->full_name)
@section('content')
@php
    $c = $customer;
    $row = fn ($label, $value) => '<div class="col-md-6 col-xl-4 mb-3"><div class="small text-body-secondary">'.e($label).'</div><div class="fw-semibold">'.(filled($value) ? e($value) : '—').'</div></div>';
@endphp
<a href="{{ route('admin.resource.index', 'customers') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Customer list</a>
<div class="card my-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center">
    @if ($c->imgguest)<img src="{{ asset($c->imgguest) }}" class="rounded-circle" width="88" height="88" style="object-fit:cover" alt="">@else<div class="rounded-circle bg-body-secondary d-flex align-items-center justify-content-center" style="width:88px;height:88px"><i class="bi bi-person fs-1"></i></div>@endif
    <div><h1 class="page-title mb-0">{{ trim(($c->title ? $c->title.' ' : '').$c->full_name) }}</h1>
        <div class="text-body-secondary">{{ $c->customernumber }} · {{ $c->cust_phone }}{{ $c->email ? ' · '.$c->email : '' }}</div>
        @if ($c->is_vip)<span class="badge text-bg-warning">VIP</span>@endif <span class="badge {{ $c->active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $c->active ? 'Active' : 'Inactive' }}</span></div>
    <div class="ms-auto d-flex flex-wrap gap-2 no-print">
        @can('reservations.create')<a class="btn btn-primary" href="{{ route('admin.reservations.create', ['guest' => $c->customerid]) }}"><i class="bi bi-calendar-plus me-1"></i>New reservation</a>@endcan
        @if ($chat)<a class="btn btn-outline-success" href="{{ $chat }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>@endif
        @can('customers.edit')<a class="btn btn-outline-primary" href="{{ route('admin.resource.edit', ['customers', $c->customerid]) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endcan
    </div>
</div></div>

<div class="row g-3 mb-4">
    @foreach ([['Bookings', $stats['bookings']], ['Completed stays', $stats['stays']], ['Nights stayed', $stats['nights']], ['Total spent', \App\Support\Money::format($stats['spent'])], ['Balance due', \App\Support\Money::format($stats['due'])]] as [$label, $value])
        <div class="col-6 col-md"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">{{ $label }}</div><div class="fs-4 fw-bold {{ $label === 'Balance due' && $stats['due'] > 0 ? 'text-danger' : '' }}">{{ $value }}</div></div></div></div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4"><div class="card-header">Details</div><div class="card-body"><div class="row">
            {!! $row('Gender', $c->gender) !!}{!! $row('Date of birth', $c->dob) !!}{!! $row('Anniversary', $c->anniversary) !!}
            {!! $row("Father's / spouse name", $c->fathername) !!}{!! $row('Occupation', $c->profession) !!}{!! $row('Nationality', $c->nationality) !!}
            {!! $row('Country', $c->country) !!}{!! $row('State', $c->state) !!}{!! $row('City', $c->city) !!}
            {!! $row('Zip code', $c->zipcode) !!}{!! $row('Address', $c->address) !!}{!! $row('Member since', $c->signupdate) !!}
            {!! $row('ID type', $c->pitype) !!}{!! $row('ID number', $c->pid) !!}{!! $row('Passport', $c->passport) !!}
            {!! $row('Visa no.', $c->visano) !!}
            @if ($c->comments)<div class="col-12"><div class="small text-body-secondary">Notes</div><div>{!! nl2br(e($c->comments)) !!}</div></div>@endif
        </div>
        @if ($c->imgfront || $c->imgback)
            <div class="d-flex gap-3 mt-2">@foreach (['imgfront' => 'ID front', 'imgback' => 'ID back'] as $col => $label)@if ($c->$col)<a href="{{ asset($c->$col) }}" target="_blank"><img src="{{ asset($c->$col) }}" height="130" class="rounded border" alt="{{ $label }}"><div class="small text-center">{{ $label }}</div></a>@endif @endforeach</div>
        @endif</div></div>

        <div class="card"><div class="card-header">Stay history</div><div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Booking</th><th>Check-in</th><th>Check-out</th><th>Rooms</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Balance</th></tr></thead><tbody>
            @forelse ($bookings as $b)
                <tr><td><a class="fw-semibold text-decoration-none" href="{{ route('admin.reservations.show', $b->booking_number) }}">#{{ $b->booking_number }}</a></td><td>{{ $b->checkindate->format('d M Y') }}</td><td>{{ $b->checkoutdate->format('d M Y') }}</td><td>{{ $b->room_no }}</td>
                    <td><span class="badge text-bg-{{ ['0' => 'warning', '1' => 'secondary', '2' => 'primary', '4' => 'info', '5' => 'success'][(string) $b->bookingstatus] ?? 'light' }}">{{ $b->status_label }}</span></td>
                    <td class="text-end">{{ \App\Support\Money::format($b->total_price) }}</td><td class="text-end">{{ \App\Support\Money::format((string) $b->bookingstatus === '1' ? 0 : $b->balance) }}</td></tr>
            @empty
                <tr><td colspan="7"><div class="empty"><i class="bi bi-calendar-x"></i>No bookings yet.</div></td></tr>
            @endforelse
            </tbody></table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header">Companions</div>
            <ul class="list-group list-group-flush">
                @forelse ($companions as $g)
                    <li class="list-group-item d-flex gap-2 align-items-center">@if ($g->occupant_image)<img src="{{ asset($g->occupant_image) }}" class="rounded" width="36" height="36" style="object-fit:cover" alt="">@endif
                        <div><div class="fw-semibold">{{ $g->guestname }}</div><div class="small text-body-secondary">{{ $g->mobile }} {{ $g->photo_id_type }} {{ $g->photo_id }}</div></div></li>
                @empty
                    <li class="list-group-item text-body-secondary">No companions recorded.</li>
                @endforelse
            </ul></div>
    </div>
</div>
@endsection
