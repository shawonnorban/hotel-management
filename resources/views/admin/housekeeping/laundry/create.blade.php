@extends('layouts.admin')
@section('title', 'New laundry order')
@section('content')
<a href="{{ route('admin.laundry.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Laundry list</a>
<h1 class="page-title mt-1 mb-4">New laundry order</h1>
@if ($products->isEmpty())<div class="alert alert-warning">Add items under <a href="{{ route('admin.resource.index', 'hk-laundry-products') }}">Laundry product list</a> and their prices under <a href="{{ route('admin.resource.index', 'hk-laundry-costs') }}">Laundry item cost</a> first.</div>@endif
<form method="post" action="{{ route('admin.laundry.store') }}">@csrf
<div class="card mb-3"><div class="card-body row g-3">
    <div class="col-md-3"><label class="form-label small fw-semibold">Booking number <span class="text-body-secondary fw-normal">(optional)</span></label><input name="booking_number" class="form-control" value="{{ old('booking_number') }}">@error('booking_number')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Guest name</label><input name="guest_name" class="form-control" value="{{ old('guest_name') }}">@error('guest_name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-2"><label class="form-label small fw-semibold">Room</label><input name="room_no" class="form-control" value="{{ old('room_no') }}"></div>
    <div class="col-md-3"><label class="form-label small fw-semibold">Date</label><input name="order_date" class="form-control" data-date value="{{ old('order_date', today()->toDateString()) }}" required></div>
</div></div>
@error('lines')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="card mb-3"><div class="card-header d-flex align-items-center">Items <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="addLine"><i class="bi bi-plus-lg"></i> Add item</button></div>
    <div class="card-body" id="lines"></div></div>
<div class="card mb-3"><div class="card-body"><label class="form-label small fw-semibold">Notes</label><input name="notes" class="form-control" maxlength="500" value="{{ old('notes') }}"></div></div>
<button class="btn btn-primary">Create order</button>
</form>
<template id="lineTpl"><div class="row g-2 mb-2 line-row">
    <div class="col-md-5"><select name="lines[__i__][product]" class="form-select">@foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><select name="lines[__i__][service]" class="form-select">@foreach ($services as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" min="1" name="lines[__i__][quantity]" value="1" class="form-control"></div>
    <div class="col-md-1"><button type="button" class="btn btn-outline-danger rm"><i class="bi bi-trash"></i></button></div></div></template>
<script>
(function(){var i=0,box=document.getElementById('lines'),tpl=document.getElementById('lineTpl');
function add(){box.insertAdjacentHTML('beforeend',tpl.innerHTML.replace(/__i__/g,i++));}
document.getElementById('addLine').addEventListener('click',add);box.addEventListener('click',function(e){var b=e.target.closest('.rm');if(b)b.closest('.line-row').remove();});add();})();
</script>
@endsection
