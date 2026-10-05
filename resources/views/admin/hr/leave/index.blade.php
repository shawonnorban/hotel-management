@extends('layouts.admin')
@section('title', 'Leave')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto"><h1 class="page-title">Leave requests</h1></div>
    @can('hr-leave.manage')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leaveModal"><i class="bi bi-plus-lg me-1"></i>New request</button>@endcan
</div>
<ul class="nav nav-pills mb-3">@foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l)<li class="nav-item"><a class="nav-link py-1 {{ ($f['status'] ?? '') === (string) $k ? 'active' : '' }}" href="{{ route('admin.hr.leave.index', array_filter(['status' => $k])) }}">{{ $l }}</a></li>@endforeach</ul>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th class="text-end">Days</th><th>Status</th><th class="text-end">Decision</th></tr></thead><tbody>
    @forelse ($requests as $r)
        <tr><td>{{ $r->employee->full_name }}</td><td>{{ $r->type->name }}@unless ($r->type->is_paid) <span class="badge text-bg-secondary">unpaid</span>@endunless</td>
            <td>{{ $r->from_date->format('d M') }} – {{ $r->to_date->format('d M Y') }}@if ($r->reason)<div class="small text-body-secondary">{{ $r->reason }}</div>@endif</td>
            <td class="text-end">{{ rtrim(rtrim($r->days, '0'), '.') }}</td>
            <td><span class="badge text-bg-{{ ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$r->status] }}">{{ ucfirst($r->status) }}</span></td>
            <td class="text-end">@if ($r->status === 'pending')@can('hr-leave.manage')<form method="post" action="{{ route('admin.hr.leave.decide', $r) }}" class="d-inline">@csrf<button name="decision" value="approve" class="btn btn-sm btn-success">Approve</button> <button name="decision" value="reject" class="btn btn-sm btn-outline-danger">Reject</button></form>@endcan @endif</td></tr>
    @empty
        <tr><td colspan="6"><div class="empty"><i class="bi bi-calendar2-x"></i>No leave requests.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($requests->hasPages())<div class="card-footer bg-transparent">{{ $requests->links() }}</div>@endif</div>
@can('hr-leave.manage')
<div class="modal fade" id="leaveModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.hr.leave.store') }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">New leave request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label small fw-semibold">Employee</label><select name="employee_id" class="form-select" required>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label small fw-semibold">Leave type</label><select name="leave_type_id" class="form-select" required>@foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}{{ $t->days_per_year ? ' ('.$t->days_per_year.' days/yr)' : '' }}</option>@endforeach</select></div>
        <div class="col-6"><label class="form-label small fw-semibold">From</label><input name="from_date" class="form-control" data-date required></div>
        <div class="col-6"><label class="form-label small fw-semibold">To</label><input name="to_date" class="form-control" data-date required></div>
        <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="half_day" value="1" id="half"><label class="form-check-label" for="half">Half day</label></div>
        <div class="col-12"><label class="form-label small fw-semibold">Reason</label><input name="reason" class="form-control" maxlength="255"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Save request</button></div></form></div></div>
@endcan
@endsection
