@extends('layouts.admin')
@section('title', $res::$label)
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto">
        <h1 class="page-title">{{ $res::$label }}</h1>
        <p class="page-sub">{{ $groups->count() }} {{ \Illuminate\Support\Str::plural('group', $groups->count()) }} · {{ $groups->sum(fn ($g) => $g['rows']->count()) }} {{ \Illuminate\Support\Str::plural('item', $groups->sum(fn ($g) => $g['rows']->count())) }}</p>
    </div>
    <form method="get" class="d-flex gap-2">
        <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="search" name="q" value="{{ $term }}" class="form-control" placeholder="Search…"></div>
    </form>
    @if (($config['add'] ?? true))
        @can($res::$slug.'.create')<a class="btn btn-primary" href="{{ route('admin.resource.create', $res::$slug) }}"><i class="bi bi-plus-lg me-1"></i>Add {{ strtolower($res::$singular) }}</a>@endcan
    @endif
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th style="width:22%">{{ $config['heading'] }}</th><th>{{ \Illuminate\Support\Str::plural($res::$singular) }}</th><th class="text-end" style="width:1%">Actions</th></tr></thead>
        <tbody>
        @forelse ($groups as $g)
            @php($manage = isset($config['manage']) ? ($config['manage'])($g['key']) : null)
            <tr>
                <td class="fw-semibold">{{ $g['title'] }}<div class="small text-body-secondary fw-normal">{{ $g['rows']->count() }} {{ \Illuminate\Support\Str::plural('item', $g['rows']->count()) }}</div></td>
                <td>
                    <div class="d-flex flex-wrap gap-2">
                    @foreach ($g['rows'] as $row)
                        @php($it = ($config['item'])($row))
                        @php($edit = ! empty($it['url']) && auth('admin')->user()->can($res::$slug.'.edit') ? $it['url'] : null)
                        @if (! empty($it['image']))
                            <span class="position-relative d-inline-block"><img src="{{ asset($it['image']) }}" alt="{{ $it['text'] ?? '' }}" class="rounded border" width="64" height="48" style="object-fit:cover">@if (! empty($it['sub']))<span class="badge text-bg-dark position-absolute bottom-0 start-0 m-1">{{ $it['sub'] }}</span>@endif</span>
                        @else
                            <{{ $edit ? 'a href='.e($edit) : 'span' }} class="badge rounded-pill text-bg-light border text-start fw-normal px-3 py-2 {{ $edit ? 'text-decoration-none' : '' }}" @if ($edit) title="Edit" @endif>
                                <span class="fw-semibold">{{ $it['text'] }}</span>@if (! empty($it['sub']))<span class="text-body-secondary ms-1">{{ $it['sub'] }}</span>@endif
                            </{{ $edit ? 'a' : 'span' }}>
                        @endif
                    @endforeach
                    </div>
                </td>
                <td class="text-end text-nowrap">@if ($manage)<a class="btn btn-sm btn-outline-primary" href="{{ $manage }}"><i class="bi bi-pencil me-1"></i>{{ $config['manage_label'] ?? 'Edit' }}</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="3"><div class="empty"><i class="bi {{ $res::$icon }}"></i>No {{ strtolower($res::$label) }} found.</div></td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
