@extends('layouts.admin')
@section('title', $role ? 'Edit role' : 'New role')
@section('content')
@php
    $locked = $role?->name === 'Super Admin';
    $held = collect(old('permissions', $role?->permissions->pluck('name')->all() ?? []));
@endphp
<a href="{{ route('admin.roles.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Roles</a>
<h1 class="page-title mt-1 mb-4">{{ $role ? 'Edit role: '.$role->name : 'New role' }}</h1>
<form method="post" action="{{ $role ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
    @csrf @if ($role) @method('PUT') @endif
    <div class="card mb-4"><div class="card-body"><label class="form-label small fw-semibold">Role name</label><input name="name" class="form-control" style="max-width:360px" value="{{ old('name', $role?->name) }}" required @readonly($locked)>
        @if ($locked)<div class="form-text">The Super Admin role always has every permission and cannot be changed.</div>@endif</div></div>
    @unless ($locked)
    <div class="card mb-4"><div class="card-header">Screens</div><div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Area</th>@foreach (['view', 'create', 'edit', 'delete'] as $a)<th class="text-center text-capitalize">{{ $a }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach ($areas->groupBy('group') as $group => $items)
            <tr class="table-light"><td colspan="5" class="small fw-semibold text-uppercase text-body-secondary">{{ $group }}</td></tr>
            @foreach ($items as $area)
                <tr><td>{{ $area['label'] }}</td>@foreach (['view', 'create', 'edit', 'delete'] as $a)
                    <td class="text-center"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $area['slug'].'.'.$a }}" @checked($held->contains($area['slug'].'.'.$a))></td>@endforeach</tr>
            @endforeach
        @endforeach
        </tbody></table></div></div>
    <div class="card mb-4"><div class="card-header">Other abilities</div><div class="card-body row g-2">
        @foreach ($extra as $name => $label)
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $name }}" id="p_{{ $name }}" @checked($held->contains($name))><label class="form-check-label" for="p_{{ $name }}">{{ $label }}</label></div></div>
        @endforeach
    </div></div>
    @endunless
    <div class="text-end"><a href="{{ route('admin.roles.index') }}" class="btn btn-light me-2">Cancel</a>@unless ($locked)<button class="btn btn-primary px-4">Save role</button>@endunless</div>
</form>
@endsection
