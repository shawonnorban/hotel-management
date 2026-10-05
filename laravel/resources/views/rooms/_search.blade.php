<form method="get" action="{{ route('rooms.index') }}" class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Check-in</label><input type="date" name="checkin" class="form-control" min="{{ date('Y-m-d') }}" value="{{ $search['checkin'] ?? '' }}" required></div>
    <div class="col-md-3"><label class="form-label">Check-out</label><input type="date" name="checkout" class="form-control" min="{{ date('Y-m-d') }}" value="{{ $search['checkout'] ?? '' }}" required></div>
    <div class="col-md-2"><label class="form-label">Adults</label><input type="number" name="adults" class="form-control" min="1" max="20" value="{{ $search['adults'] ?? 2 }}"></div>
    <div class="col-md-2"><label class="form-label">Children</label><input type="number" name="children" class="form-control" min="0" max="20" value="{{ $search['children'] ?? 0 }}"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div>
</form>
