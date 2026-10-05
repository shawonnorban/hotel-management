@extends('layouts.admin')
@section('title', 'Payroll')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto"><h1 class="page-title">Payroll</h1><p class="page-sub">Monthly salary runs.</p></div>
    @can('hr-payroll.run')
    <form method="post" action="{{ route('admin.hr.payroll.generate') }}" class="d-flex gap-2">@csrf<input type="month" name="period" class="form-control" value="{{ now()->format('Y-m') }}" required><button class="btn btn-primary text-nowrap">Generate payroll</button></form>
    @endcan
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Month</th><th>Employees</th><th class="text-end">Cost</th><th class="text-end">Net pay</th><th>Status</th></tr></thead><tbody>
    @forelse ($runs as $run)
        <tr><td><a class="fw-semibold text-decoration-none" href="{{ route('admin.hr.payroll.show', $run) }}">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $run->period.'-01')->format('F Y') }}</a></td><td>{{ $run->items_count }}</td>
            <td class="text-end">{{ \App\Support\Money::format($run->total_expense) }}</td><td class="text-end">{{ \App\Support\Money::format($run->total_net) }}</td>
            <td><span class="badge text-bg-{{ ['draft' => 'secondary', 'finalized' => 'warning', 'paid' => 'success'][$run->status] }}">{{ ucfirst($run->status) }}</span></td></tr>
    @empty
        <tr><td colspan="5"><div class="empty"><i class="bi bi-cash-stack"></i>No payroll has been run yet.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($runs->hasPages())<div class="card-footer bg-transparent">{{ $runs->links() }}</div>@endif</div>
@endsection
