@extends('layouts.admin')
@section('title', 'Backups')
@section('content')
<div class="d-flex align-items-center mb-4"><div class="me-auto"><h1 class="page-title">Backups</h1><p class="page-sub">A backup is created automatically every night at 02:00 (needs the scheduler, see the README). The newest 14 are kept.</p></div>
    <form method="post" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-primary"><i class="bi bi-database-down me-1"></i>Back up now</button></form></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>File</th><th>Created</th><th class="text-end">Size</th><th class="text-end">Actions</th></tr></thead><tbody>
    @forelse ($backups as $b)
        <tr><td class="font-monospace small">{{ $b['name'] }}</td><td>{{ \Illuminate\Support\Carbon::createFromTimestamp($b['modified'])->format('d M Y H:i') }}</td><td class="text-end">{{ number_format($b['size'] / 1024, 1) }} KB</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.backups.download', $b['name']) }}"><i class="bi bi-download"></i></a>
                <form method="post" action="{{ route('admin.backups.destroy', $b['name']) }}" class="d-inline" data-confirm="Delete this backup?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
    @empty
        <tr><td colspan="4"><div class="empty"><i class="bi bi-database"></i>No backups yet.</div></td></tr>
    @endforelse
    </tbody></table></div></div>
<p class="small text-body-secondary mt-3">Restore with <code>gunzip &lt; backup-….sql.gz | mysql -u USER -p DATABASE</code>. Backups contain personal data: keep them private.</p>
@endsection
