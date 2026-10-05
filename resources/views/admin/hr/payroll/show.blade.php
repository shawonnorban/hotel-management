@extends('layouts.admin')
@section('title', 'Payroll '.$run->period)
@section('content')
@php($money = fn ($v) => \App\Support\Money::format($v))
<a href="{{ route('admin.hr.payroll.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Payroll</a>
<div class="d-flex flex-wrap align-items-center gap-2 mt-1 mb-4">
    <h1 class="page-title me-2">{{ $month->format('F Y') }}</h1>
    <span class="badge text-bg-{{ ['draft' => 'secondary', 'finalized' => 'warning', 'paid' => 'success'][$run->status] }} fs-6">{{ ucfirst($run->status) }}</span>
    <div class="ms-auto d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.hr.payroll.export', $run) }}"><i class="bi bi-download me-1"></i>CSV</a>
        @can('hr-payroll.run')
            @if ($run->status === 'draft')
                <form method="post" action="{{ route('admin.hr.payroll.generate') }}">@csrf<input type="hidden" name="period" value="{{ $run->period }}"><button class="btn btn-outline-primary">Recalculate</button></form>
                <form method="post" action="{{ route('admin.hr.payroll.finalize', $run) }}" data-confirm="Finalize this payroll? The expense is booked and loan instalments are taken.">@csrf<button class="btn btn-primary">Finalize</button></form>
                <form method="post" action="{{ route('admin.hr.payroll.destroy', $run) }}" data-confirm="Delete this draft?">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
            @elseif ($run->status === 'finalized')
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal">Pay salaries</button>
                <form method="post" action="{{ route('admin.hr.payroll.reopen', $run) }}" data-confirm="Reopen this payroll as a draft?">@csrf<button class="btn btn-outline-secondary">Reopen</button></form>
            @endif
        @endcan
    </div>
</div>
<div class="row g-3 mb-4">
    @foreach ([['Cost to hotel', $run->total_expense], ['Deductions', $run->total_deductions], ['Loan instalments', $run->total_loans], ['Net pay', $run->total_net]] as [$l, $v])
        <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="text-body-secondary small text-uppercase">{{ $l }}</div><div class="fs-4 fw-bold">{{ $money($v) }}</div></div></div></div>
    @endforeach
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Employee</th><th class="text-end">Basic</th><th class="text-end">Allowances</th><th class="text-end">Bonus</th><th class="text-end">Absence</th><th class="text-end">Deductions</th><th class="text-end">Loan</th><th class="text-end">Net</th><th></th></tr></thead><tbody>
    @forelse ($run->items as $i)
        <tr><td>{{ $i->employee->full_name }}<div class="small text-body-secondary">{{ $i->employee->code }}@if ((float) $i->absent_days > 0) · {{ rtrim(rtrim($i->absent_days, '0'), '.') }} day(s) absent @endif</div></td>
            <td class="text-end">{{ $money($i->basic) }}</td><td class="text-end">{{ $money($i->allowances) }}</td><td class="text-end">{{ $money($i->bonus) }}</td>
            <td class="text-end text-danger">{{ (float) $i->absence_deduction ? '−'.$money($i->absence_deduction) : '—' }}</td><td class="text-end text-danger">{{ (float) $i->deductions ? '−'.$money($i->deductions) : '—' }}</td><td class="text-end text-danger">{{ (float) $i->loan_deduction ? '−'.$money($i->loan_deduction) : '—' }}</td>
            <td class="text-end fw-bold">{{ $money($i->net) }}</td>
            <td class="text-end">@if ($run->status !== 'draft')<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.hr.payroll.payslip', [$run, $i]) }}" target="_blank" title="Payslip"><i class="bi bi-file-earmark-pdf"></i></a>@endif</td></tr>
    @empty
        <tr><td colspan="9"><div class="empty"><i class="bi bi-people"></i>No employees were eligible for this month.</div></td></tr>
    @endforelse
    </tbody></table></div></div>
@can('hr-payroll.run')
<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.hr.payroll.pay', $run) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Pay salaries</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3"><div class="col-12">Total net pay: <strong>{{ $money($run->total_net) }}</strong></div>
        <div class="col-6"><label class="form-label small fw-semibold">Paid from</label><select name="account" class="form-select" required>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-6"><label class="form-label small fw-semibold">Date</label><input name="paid_on" class="form-control" data-date value="{{ now()->format('Y-m-d') }}" required></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-success">Mark as paid</button></div></form></div></div>
@endcan
@endsection
