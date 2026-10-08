<div class="border rounded mb-3 room-row" data-i="{{ $i }}">
    <div class="px-3 py-2 border-bottom d-flex align-items-center"><span class="small fw-semibold">Room Info</span>
        <button type="button" class="btn btn-sm btn-danger ms-auto rm-room" aria-label="Remove this room type"><i class="bi bi-trash"></i></button></div>
    <div class="p-3 row g-3">
        <div class="col-md-5"><label class="form-label small fw-semibold">Room Type <span class="text-danger">*</span></label>
            <select name="lines[{{ $i }}][room]" class="form-select room-type" required><option value="">Choose Room Type</option>@foreach ($rooms as $r)<option value="{{ $r->roomid }}" @selected((string) ($row['room'] ?? '') === (string) $r->roomid)>{{ $r->roomtype }} · {{ \App\Support\Money::format($r->rate) }} · sleeps {{ $r->capacity }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Rooms</label><input type="number" name="lines[{{ $i }}][rooms]" min="1" max="20" class="form-control room-count" value="{{ $row['rooms'] ?? 1 }}" required></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">#Adults</label><input type="number" name="lines[{{ $i }}][adults]" min="1" class="form-control room-adults" value="{{ $row['adults'] ?? 2 }}" required></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">#Children</label><input type="number" name="lines[{{ $i }}][children]" min="0" class="form-control room-children" value="{{ $row['children'] ?? 0 }}"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Room No. <span class="text-body-secondary fw-normal">(optional — tick the rooms you want; any you don't tick are picked for you)</span></label>
            <div class="room-numbers d-flex flex-wrap gap-2">@foreach ((array) ($row['numbers'] ?? []) as $n)<label class="btn btn-sm btn-outline-secondary mb-0"><input type="checkbox" class="form-check-input me-1" name="lines[{{ $i }}][numbers][]" value="{{ $n }}" checked>{{ $n }}</label>@endforeach</div>
            <div class="small mt-1 room-avail"></div></div>
    </div>
</div>
