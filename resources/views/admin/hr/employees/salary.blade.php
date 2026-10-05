@extends('layouts.admin')
@section('title', 'Salary set-up')
@section('content')
<a href="{{ route('admin.resource.index', 'hr-employees') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Employees</a>
<h1 class="page-title mt-1">{{ $employee->full_name }}</h1>
<p class="page-sub mb-4">{{ $employee->code }} · {{ $employee->position?->title }} · {{ $employee->department?->name }}</p>
@php($rows = old('components', $employee->components->map(fn ($c) => ['name' => $c->name, 'kind' => $c->kind, 'amount' => $c->amount, 'is_percent' => $c->is_percent])->all()))
<form method="post" action="{{ route('admin.hr.employees.salary.update', $employee) }}" class="card" style="max-width:860px">
    @csrf @method('PUT')
    <div class="card-body">
        <div class="mb-4" style="max-width:280px"><label class="form-label small fw-semibold">Basic salary (per month)</label><input type="number" step="0.01" min="0" name="basic_salary" class="form-control" value="{{ old('basic_salary', $employee->basic_salary) }}" required @cannot('hr-payroll.run') readonly @endcannot></div>
        <div class="d-flex align-items-center mb-2"><h2 class="h6 mb-0">Allowances &amp; deductions</h2>@can('hr-payroll.run')<button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="addRow"><i class="bi bi-plus-lg me-1"></i>Add</button>@endcan</div>
        <table class="table table-sm align-middle" id="comp"><thead><tr><th>Name</th><th style="width:150px">Type</th><th style="width:150px" class="text-end">Amount</th><th style="width:110px">% of basic</th><th></th></tr></thead><tbody></tbody></table>
        @if (auth('admin')->user()->can('hr-payroll.run'))<div class="small text-body-secondary">Allowances increase the pay (e.g. transport, housing); deductions reduce it (e.g. tax, insurance).</div>@endif
    </div>
    @can('hr-payroll.run')<div class="card-footer bg-transparent text-end"><button class="btn btn-primary px-4">Save</button></div>@endcan
</form>
@push('scripts')
<script>
(function () {
    var body = document.querySelector('#comp tbody'), n = 0, rows = @json($rows);
    function add(r) {
        r = r || {}; var k = n++, tr = document.createElement('tr');
        tr.innerHTML = '<td><input name="components[' + k + '][name]" class="form-control form-control-sm" maxlength="80" value="' + String(r.name || '').replace(/"/g, '&quot;') + '"></td>' +
            '<td><select name="components[' + k + '][kind]" class="form-select form-select-sm"><option value="allowance"' + (r.kind === 'allowance' ? ' selected' : '') + '>Allowance</option><option value="deduction"' + (r.kind === 'deduction' ? ' selected' : '') + '>Deduction</option></select></td>' +
            '<td><input type="number" step="0.01" min="0" name="components[' + k + '][amount]" class="form-control form-control-sm text-end" value="' + (r.amount || '') + '"></td>' +
            '<td class="text-center"><input type="hidden" name="components[' + k + '][is_percent]" value="0"><input type="checkbox" class="form-check-input" name="components[' + k + '][is_percent]" value="1"' + (r.is_percent ? ' checked' : '') + '></td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger js-rm"><i class="bi bi-x-lg"></i></button></td>';
        body.appendChild(tr);
    }
    document.getElementById('addRow')?.addEventListener('click', function () { add(); });
    body.addEventListener('click', function (e) { var b = e.target.closest('.js-rm'); if (b) { b.closest('tr').remove(); } });
    rows.forEach(add);
})();
</script>
@endpush
@endsection
