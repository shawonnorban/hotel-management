@extends('layouts.admin')
@section('title', 'Roles')
@section('content')
<div class="d-flex align-items-center mb-4"><div class="me-auto"><h1 class="page-title">Roles &amp; permissions</h1><p class="page-sub">Decide who can see and do what.</p></div>
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New role</a></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Role</th><th>Permissions</th><th>Staff</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @foreach ($roles as $role)
        <tr><td class="fw-semibold">{{ $role->name }}@if ($role->name === 'Super Admin') <span class="badge text-bg-warning ms-1">Full access</span>@endif</td>
            <td>{{ $role->name === 'Super Admin' ? 'All' : $role->permissions_count }}</td><td>{{ $role->users_count }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.roles.edit', $role) }}"><i class="bi bi-pencil"></i></a>
                @if ($role->name !== 'Super Admin')<form method="post" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline" data-confirm="Delete the role “{{ $role->name }}”?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>@endif</td></tr>
    @endforeach
    </tbody></table></div></div>
@endsection
