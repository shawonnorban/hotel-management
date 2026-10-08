@extends('layouts.admin')
@section('title', 'WhatsApp messages')
@section('content')
<div class="mb-4"><h1 class="page-title">WhatsApp messages</h1><p class="page-sub">Messages sent from the system.</p></div>
@if (! $apiEnabled)<div class="alert alert-info">Sending is off. <a href="{{ route('admin.whatsapp.settings') }}">Connect the Cloud API</a> to send from here; booking pages still offer a click-to-chat button.</div>
@else
<form method="post" action="{{ route('admin.whatsapp.send') }}" class="card mb-4"><div class="card-body row g-2">@csrf
    <div class="col-md-3"><input name="to" class="form-control" placeholder="Phone with country code" value="{{ old('to') }}" required>@error('to')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-7"><input name="body" class="form-control" placeholder="Message" maxlength="1000" value="{{ old('body') }}" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Send</button></div>
</div></form>@endif
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>When</th><th>To</th><th>Message</th><th>Status</th></tr></thead><tbody>
    @forelse ($messages as $m)<tr><td class="text-nowrap">{{ $m->created_at->format('d M H:i') }}</td><td>{{ $m->to }}</td><td>{{ $m->body }}</td><td><span class="badge text-bg-{{ $m->status === 'sent' ? 'success' : 'danger' }}">{{ ucfirst($m->status) }}</span>@if ($m->error)<div class="small text-danger">{{ $m->error }}</div>@endif</td></tr>
    @empty<tr><td colspan="4"><div class="empty"><i class="bi bi-whatsapp"></i>Nothing sent yet.</div></td></tr>@endforelse</tbody></table></div>
    @if ($messages->hasPages())<div class="card-footer bg-transparent">{{ $messages->links() }}</div>@endif</div>
@endsection
