@php
    $name = $field->name;
    $value = old($name, $record ? $record->getAttribute($name) : $field->default);
    $id = 'f_'.$name;
    $invalid = $errors->has($name) ? ' is-invalid' : '';
    $attrs = collect($field->attributes)->map(fn ($v, $k) => $k.'="'.e($v).'"')->implode(' ');
@endphp
@if ($field->type === 'toggle')
    <div class="form-check form-switch pt-4">
        <input type="hidden" name="{{ $name }}" value="0">
        <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}" value="1" @checked((int) $value === 1)>
        <label class="form-check-label" for="{{ $id }}">{{ $field->label }}</label>
    </div>
@else
    <label for="{{ $id }}" class="form-label fw-semibold small">{{ $field->label }}@if ($field->isRequired())<span class="text-danger"> *</span>@endif</label>
    @switch($field->type)
        @case('textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="form-control{{ $invalid }}" placeholder="{{ $field->placeholder }}" {!! $attrs !!}>{{ $value }}</textarea>
            @break
        @case('select')
            <select id="{{ $id }}" name="{{ $name }}" class="form-select{{ $invalid }}" data-search {!! $attrs !!}>
                <option value="">— Select —</option>
                @foreach ($field->resolveOptions() as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
            @break
        @case('image')
            @if ($record && $value)<div class="mb-2"><img src="{{ asset($value) }}" alt="" class="rounded border" style="height:80px"></div>@endif
            <input type="file" id="{{ $id }}" name="{{ $name }}" accept="image/*" class="form-control{{ $invalid }}">
            @break
        @case('date')
            <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '' }}" class="form-control{{ $invalid }}" data-date placeholder="YYYY-MM-DD" autocomplete="off">
            @break
        @case('datetime')
            <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i') : '' }}" class="form-control{{ $invalid }}" data-datetime autocomplete="off">
            @break
        @case('time')
            <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="form-control{{ $invalid }}" data-time autocomplete="off">
            @break
        @case('password')
            <input type="password" id="{{ $id }}" name="{{ $name }}" class="form-control{{ $invalid }}" autocomplete="new-password" placeholder="{{ $record ? 'Leave blank to keep the current password' : '' }}">
            @break
        @default
            @php($inputType = match ($field->type) { 'email' => 'email', 'number' => 'number', 'decimal', 'money' => 'number', default => 'text' })
            @if ($field->prefix || $field->type === 'money')
                <div class="input-group">
                    @if ($field->prefix)<span class="input-group-text">{{ $field->prefix }}</span>@endif
                    <input type="{{ $inputType }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="form-control{{ $invalid }}" @if (in_array($field->type, ['decimal', 'money'])) step="0.01" @endif @if ($field->type === 'number') step="1" @endif placeholder="{{ $field->placeholder }}" {!! $attrs !!}>
                </div>
            @else
                <input type="{{ $inputType }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="form-control{{ $invalid }}" @if (in_array($field->type, ['decimal', 'money'])) step="0.01" @endif @if ($field->type === 'number') step="1" @endif placeholder="{{ $field->placeholder }}" {!! $attrs !!}>
            @endif
    @endswitch
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if ($field->help)<div class="form-text">{{ $field->help }}</div>@endif
@endif
