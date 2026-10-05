<form method="get" class="card mb-4 no-print"><div class="card-body row g-3 align-items-end">
    {{ $slot ?? '' }}
    <div class="col-md-3"><label class="form-label small fw-semibold">From</label><input name="from" class="form-control" data-date value="{{ $from->format('Y-m-d') }}"></div>
    <div class="col-md-3"><label class="form-label small fw-semibold">To</label><input name="to" class="form-control" data-date value="{{ $to->format('Y-m-d') }}"></div>
    <div class="col-auto"><button class="btn btn-primary">Show</button></div>
    <div class="col-auto ms-auto d-flex gap-2"><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download me-1"></i>CSV</a><button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></div>
</div></form>
