<form method="get" action="{{ route('rooms.index') }}" class="row g-3 align-items-end">
    <div class="col-6 col-md-3"><label class="form-label small fw-semibold mb-1"><i class="bi bi-calendar-event me-1"></i>Check-in</label><input type="text" name="checkin" class="form-control" data-date data-min="today" value="{{ $search['checkin'] ?? '' }}" placeholder="Select date" required autocomplete="off"></div>
    <div class="col-6 col-md-3"><label class="form-label small fw-semibold mb-1"><i class="bi bi-calendar-check me-1"></i>Check-out</label><input type="text" name="checkout" class="form-control" data-date data-min="today" value="{{ $search['checkout'] ?? '' }}" placeholder="Select date" required autocomplete="off"></div>
    <div class="col-4 col-md-2"><label class="form-label small fw-semibold mb-1">Adults</label><input type="number" name="adults" class="form-control" min="1" max="20" value="{{ $search['adults'] ?? 2 }}"></div>
    <div class="col-4 col-md-2"><label class="form-label small fw-semibold mb-1">Children</label><input type="number" name="children" class="form-control" min="0" max="20" value="{{ $search['children'] ?? 0 }}"></div>
    <div class="col-4 col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
</form>
