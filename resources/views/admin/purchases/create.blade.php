@extends('layouts.admin')
@section('title', 'New purchase')
@section('content')
<a href="{{ route('admin.purchases.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Purchases</a>
<h1 class="page-title mt-1 mb-4">New purchase</h1>
<form method="post" action="{{ route('admin.purchases.store') }}" id="poForm">
    @csrf
    <div class="card mb-4"><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label small fw-semibold">Supplier</label><select name="supplier_id" class="form-select" data-search required><option value="">— Select —</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected((int) old('supplier_id') === $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Date</label><input name="purchase_date" class="form-control" data-date value="{{ old('purchase_date', now()->format('Y-m-d')) }}" required></div>
        <div class="col-md-5"><label class="form-label small fw-semibold">Supplier invoice no.</label><input name="reference" class="form-control" maxlength="80" value="{{ old('reference') }}"></div>
    </div></div>
    <div class="card mb-4"><div class="card-header d-flex align-items-center">Items<button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="addLine"><i class="bi bi-plus-lg me-1"></i>Add line</button></div>
        <div class="table-responsive"><table class="table align-middle mb-0" id="lines">
            <thead><tr><th style="width:42%">Item</th><th style="width:16%" class="text-end">Quantity</th><th style="width:18%" class="text-end">Unit cost</th><th class="text-end">Line total</th><th></th></tr></thead>
            <tbody></tbody>
            <tfoot>
                <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end" id="subtotal">0.00</td><td></td></tr>
                <tr><td colspan="3" class="text-end align-middle">Discount</td><td><input type="number" step="0.01" min="0" name="discount" id="discount" class="form-control form-control-sm text-end" value="{{ old('discount', 0) }}"></td><td></td></tr>
                <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end" id="total">0.00</td><td></td></tr>
            </tfoot></table></div></div>
    <div class="row g-4">
        <div class="col-md-7"><div class="card h-100"><div class="card-header">Notes</div><div class="card-body"><textarea name="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea></div></div></div>
        <div class="col-md-5"><div class="card h-100"><div class="card-header">Pay now (optional)</div><div class="card-body row g-2">
            <div class="col-6"><label class="form-label small fw-semibold">Amount</label><input type="number" step="0.01" min="0" name="pay_amount" class="form-control" value="{{ old('pay_amount') }}"></div>
            <div class="col-6"><label class="form-label small fw-semibold">Pay from</label><select name="pay_account" class="form-select"><option value="">—</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected((int) old('pay_account') === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label small fw-semibold">Reference</label><input name="pay_reference" class="form-control" maxlength="80" value="{{ old('pay_reference') }}"></div>
        </div></div></div>
    </div>
    <div class="text-end mt-4"><button class="btn btn-primary px-4">Record purchase</button></div>
</form>
@push('scripts')
<script>
(function () {
    var items = @json($items->map(fn ($i) => ['id' => $i->id, 'label' => $i->name.' ('.$i->unit->short_code.')', 'cost' => (float) $i->avg_cost])->values());
    var old = @json(old('lines', []));
    var body = document.querySelector('#lines tbody'), n = 0;
    function opts(sel) { return '<option value="">— Select —</option>' + items.map(function (i) { return '<option value="' + i.id + '"' + (String(sel) === String(i.id) ? ' selected' : '') + '>' + i.label.replace(/</g, '&lt;') + '</option>'; }).join(''); }
    function add(line) {
        line = line || {}; var k = n++, tr = document.createElement('tr');
        tr.innerHTML = '<td><select name="lines[' + k + '][item]" class="form-select form-select-sm js-item" required>' + opts(line.item) + '</select></td>' +
            '<td><input type="number" step="0.001" min="0.001" name="lines[' + k + '][quantity]" class="form-control form-control-sm text-end js-qty" value="' + (line.quantity || '') + '" required></td>' +
            '<td><input type="number" step="0.0001" min="0" name="lines[' + k + '][unit_cost]" class="form-control form-control-sm text-end js-cost" value="' + (line.unit_cost || '') + '" required></td>' +
            '<td class="text-end js-line">0.00</td><td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger js-remove"><i class="bi bi-x-lg"></i></button></td>';
        body.appendChild(tr); totals();
    }
    function totals() {
        var sub = 0;
        body.querySelectorAll('tr').forEach(function (tr) {
            var t = (parseFloat(tr.querySelector('.js-qty').value) || 0) * (parseFloat(tr.querySelector('.js-cost').value) || 0);
            tr.querySelector('.js-line').textContent = t.toFixed(2); sub += t;
        });
        var d = parseFloat(document.getElementById('discount').value) || 0;
        document.getElementById('subtotal').textContent = sub.toFixed(2);
        document.getElementById('total').textContent = Math.max(0, sub - d).toFixed(2);
    }
    document.getElementById('addLine').addEventListener('click', function () { add(); });
    body.addEventListener('input', totals);
    document.getElementById('discount').addEventListener('input', totals);
    body.addEventListener('change', function (e) {
        if (e.target.classList.contains('js-item')) {
            var item = items.find(function (i) { return String(i.id) === e.target.value; }), cost = e.target.closest('tr').querySelector('.js-cost');
            if (item && !cost.value) { cost.value = item.cost.toFixed(4); totals(); }
        }
    });
    body.addEventListener('click', function (e) { var b = e.target.closest('.js-remove'); if (b && body.children.length > 1) { b.closest('tr').remove(); totals(); } });
    (Object.values(old).length ? Object.values(old) : [{}]).forEach(add);
})();
</script>
@endpush
@endsection
