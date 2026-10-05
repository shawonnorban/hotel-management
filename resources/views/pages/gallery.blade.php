@extends('layouts.site')
@section('title', 'Gallery')
@section('content')
<div class="container">
    <h1 class="section-title mb-4">Gallery</h1>
    @if ($images->isEmpty())
        <div class="empty card"><i class="bi bi-images"></i>Photos will appear here soon.</div>
    @else
        <div class="row g-3">
            @foreach ($images as $image)
                <div class="col-6 col-md-4 col-lg-3"><a href="{{ asset($image->room_imagename) }}" target="_blank" class="d-block position-relative rounded-3 overflow-hidden shadow-sm">
                    <img src="{{ asset($image->room_imagename) }}" alt="{{ $rooms[$image->room_id] ?? 'Room' }}" class="w-100" style="height:200px;object-fit:cover" loading="lazy">
                    <span class="position-absolute bottom-0 start-0 end-0 p-2 small text-white" style="background:linear-gradient(transparent,rgba(0,0,0,.65))">{{ $rooms[$image->room_id] ?? '' }}</span></a></div>
            @endforeach
        </div>
    @endif
</div>
@endsection
