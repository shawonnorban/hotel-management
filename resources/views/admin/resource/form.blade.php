@extends('layouts.admin')
@section('title', ($record ? 'Edit ' : 'Add ').strtolower($res::$singular))
@section('content')
<div class="mb-4">
    <a href="{{ route('admin.resource.index', $res::$slug) }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> {{ $res::$label }}</a>
    <h1 class="page-title mt-1">{{ $record ? 'Edit' : 'Add' }} {{ strtolower($res::$singular) }}</h1>
</div>
<form method="post" enctype="multipart/form-data" novalidate
      action="{{ $record ? route('admin.resource.update', [$res::$slug, $record->getKey()]) : route('admin.resource.store', $res::$slug) }}">
    @csrf
    @if ($record) @method('PUT') @endif
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                @foreach ($fields as $field)
                    <div class="col-12 col-md-{{ $field->col }}">@include('admin.resource._field', ['field' => $field, 'record' => $record])</div>
                @endforeach
            </div>
        </div>
        <div class="card-footer bg-transparent d-flex gap-2 justify-content-end">
            <a class="btn btn-light" href="{{ route('admin.resource.index', $res::$slug) }}">Cancel</a>
            <button class="btn btn-primary px-4">{{ $record ? 'Save changes' : 'Create' }}</button>
        </div>
    </div>
</form>
@endsection
