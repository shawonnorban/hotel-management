@extends('layouts.admin')
@section('title', 'Staff loans')
@section('content')
<div class="d-flex align-items-center mb-4"><div class="me-auto"><h1 class="page-title">Staff loans</h1><p class="page-sub">Repaid in equal instalments through payroll.</p></div>
    @can('hr-loans.manage')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loanModal"><i class="bi bi-plus-lg me-1"></i>Issue loan</button>@endcan</div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Employee</th><th>Issued</th><th class="text-end">Amount</th><th>Instalments</th><th class="text-end">Outstanding</th><th>Status</th></tr></thead><tbody>
    @forelse ($loans as $loan)
        <tr><td>{{ $loan->employee->full_name }}<div class="small text-body-secondary">{{ $loan->reason }}</div></td><td>{{ $loan->issued_on->format('d M Y') }}</td><td class="text-end">{{ \App\Support\Money::format($loan->amount) }}</td>
            <td>{{ $loan->schedule->where('deducted', true)->count() }} / {{ $loan->installments }} from {{ $loan->first_month }}</td>
            <td class="text-end">{{ \App\Support\Money::format($loan->outstanding) }}</td><td><span class="badge text-bg-{{ $loan->status === 'active' ? 'warning' : 'success' }}">{{ ucfirst($loan->status) }}</span></td></tr>
    @empty
        <tr><td colspan="6"><div class="empty"><i class="bi bi-cash-coin"></i>No loans.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($loans->hasPages())<div class="card-footer bg-transparent">{{ $loans->links() }}</div>@endif</div>
@can('hr-loans.manage')
<div class="modal fade" id="loanModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.hr.loans.store') }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Issue a loan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label small fw-semibold">Employee</label><select name="employee_id" class="form-select" required>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
        <div class="col-6"><label class="form-label small fw-semibold">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
        <div class="col-6"><label class="form-label small fw-semibold">Instalments</label><input type="number" min="1" max="120" name="installments" class="form-control" value="6" required></div>
        <div class="col-6"><label class="form-label small fw-semibold">Issued on</label><input name="issued_on" class="form-control" data-date value="{{ now()->format('Y-m-d') }}" required></div>
        <div class="col-6"><label class="form-label small fw-semibold">First deduction month</label><input type="month" name="first_month" class="form-control" value="{{ now()->addMonth()->format('Y-m') }}" required></div>
        <div class="col-12"><label class="form-label small fw-semibold">Paid from</label><select name="account" class="form-select" required>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label small fw-semibold">Reason</label><input name="reason" class="form-control" maxlength="255"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Issue loan</button></div></form></div></div>
@endcan
@endsection
