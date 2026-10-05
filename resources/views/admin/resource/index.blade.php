@extends('layouts.admin')
@section('title', $res::$label)
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto">
        <h1 class="page-title">{{ $res::$label }}</h1>
        <p class="page-sub">{{ number_format($rows->total()) }} {{ \Illuminate\Support\Str::plural('record', $rows->total()) }}</p>
    </div>
    <form method="get" class="d-flex gap-2">
        <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="search" name="q" value="{{ $term }}" class="form-control" placeholder="Search…"></div>
    </form>
    @can($res::$slug.'.view')
        <a class="btn btn-outline-secondary" href="{{ route('admin.resource.index', [$res::$slug, 'export' => 'csv', 'q' => $term]) }}"><i class="bi bi-download me-1"></i>CSV</a>
    @endcan
    @can($res::$slug.'.create')
        <a class="btn btn-primary" href="{{ route('admin.resource.create', $res::$slug) }}"><i class="bi bi-plus-lg me-1"></i>Add {{ strtolower($res::$singular) }}</a>
    @endcan
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>@foreach ($listed as $field)<th>{{ $field->label }}</th>@endforeach<th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($listed as $field)<td>{{ \App\Admin\Formatter::cell($field, $row) }}</td>@endforeach
                    <td class="text-end text-nowrap">
                        @can($res::$slug.'.edit')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.resource.edit', [$res::$slug, $row->getKey()]) }}"><i class="bi bi-pencil"></i></a>@endcan
                        @can($res::$slug.'.delete')
                            <form method="post" action="{{ route('admin.resource.destroy', [$res::$slug, $row->getKey()]) }}" class="d-inline" data-confirm="Delete this {{ strtolower($res::$singular) }}? This cannot be undone.">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $listed->count() + 1 }}"><div class="empty"><i class="bi {{ $res::$icon }}"></i>No {{ strtolower($res::$label) }} found.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($rows->hasPages())<div class="card-footer bg-transparent">{{ $rows->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
