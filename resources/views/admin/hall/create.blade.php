@extends('layouts.admin')
@section('title', 'New hall booking')
@section('content')
<a href="{{ route('admin.hall-bookings.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Hallroom booking</a>
<h1 class="page-title mt-1 mb-4">New hall booking</h1>
@if ($halls->isEmpty())<div class="alert alert-warning">Add a hall first under <a href="{{ route('admin.resource.index', 'hall-rooms') }}">Hallroom assign</a>.</div>@endif
@error('hall_id')<div class="alert alert-danger">{{ $message }}</div>@enderror
<form method="post" action="{{ route('admin.hall-bookings.store') }}">@csrf
<div class="card mb-3"><div class="card-header">Event</div><div class="card-body row g-3">
    <div class="col-md-4"><label class="form-label small fw-semibold">Hall</label><select name="hall_id" id="hall" class="form-select" required>@foreach ($halls as $h)<option value="{{ $h->id }}" data-cap="{{ $h->capacity }}" data-hour="{{ $h->rate_per_hour }}" data-day="{{ $h->rate_per_day }}" @selected(old('hall_id') == $h->id)>{{ $h->name }} · up to {{ $h->capacity }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Event name</label><input name="event_name" class="form-control" value="{{ old('event_name') }}" required>@error('event_name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Seat plan</label><select name="seat_plan_id" class="form-select"><option value="">— None —</option>@foreach ($halls as $h)@foreach ($h->seatPlans as $p)<option value="{{ $p->id }}" @selected(old('seat_plan_id') == $p->id)>{{ $h->name }}: {{ $p->name }} ({{ $p->seats }} seats)</option>@endforeach @endforeach</select>@error('seat_plan_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label small fw-semibold">Date</label><input name="event_date" class="form-control" data-date value="{{ old('event_date', today()->toDateString()) }}" required>@error('event_date')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-2"><label class="form-label small fw-semibold">From</label><input type="time" name="starts_at" id="from" class="form-control" value="{{ old('starts_at', '10:00') }}" required></div>
    <div class="col-md-2"><label class="form-label small fw-semibold">To</label><input type="time" name="ends_at" id="to" class="form-control" value="{{ old('ends_at', '14:00') }}" required></div>
    <div class="col-md-2"><label class="form-label small fw-semibold">Guests</label><input type="number" min="1" name="guests" class="form-control" value="{{ old('guests', 50) }}" required></div>
    <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="confirmed">Confirmed</option><option value="tentative" @selected(old('status') === 'tentative')>Tentative</option></select></div>
</div></div>
<div class="card mb-3"><div class="card-header">Customer</div><div class="card-body row g-3">
    <div class="col-md-4"><label class="form-label small fw-semibold">Name</label><input name="customer_name" class="form-control" value="{{ old('customer_name') }}" required></div>
    <div class="col-md-3"><label class="form-label small fw-semibold">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
    <div class="col-md-3"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}">@error('email')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-2"><label class="form-label small fw-semibold">Room booking no.</label><input name="booking_number" class="form-control" value="{{ old('booking_number') }}"></div>
</div></div>
<div class="card mb-3"><div class="card-header">Price</div><div class="card-body row g-3 align-items-end">
    <div class="col-md-3"><label class="form-label small fw-semibold">Discount</label><input type="number" step="0.01" min="0" name="discount" id="discount" class="form-control" value="{{ old('discount', 0) }}"></div>
    <div class="col-md-5"><div class="small text-body-secondary">Estimate (final price is calculated on save)</div><div class="fs-4 fw-bold" id="est">—</div></div>
    <div class="col-12"><label class="form-label small fw-semibold">Notes</label><input name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}"></div>
</div></div>
<button class="btn btn-primary">Create booking</button>
</form>
<script>
(function(){var h=document.getElementById('hall'),f=document.getElementById('from'),t=document.getElementById('to'),d=document.getElementById('discount'),e=document.getElementById('est');
function calc(){var o=h.options[h.selectedIndex];if(!o||!f.value||!t.value){e.textContent='—';return;}
var a=f.value.split(':'),b=t.value.split(':'),m=(b[0]*60+ +b[1])-(a[0]*60+ +a[1]);if(m<=0)m+=1440;
var s=m/60*parseFloat(o.dataset.hour);var day=parseFloat(o.dataset.day);if(day>0)s=Math.min(s,day);s=Math.max(0,s-(parseFloat(d.value)||0));e.textContent=s.toFixed(2)+' ('+(m/60).toFixed(1)+' h)';}
[h,f,t,d].forEach(function(x){x.addEventListener('input',calc);});calc();})();
</script>
@endsection
