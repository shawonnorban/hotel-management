@extends('layouts.site')
@section('title', $page->title)
@section('content')
<div class="container"><div class="row justify-content-center"><div class="col-lg-8">
    <h1 class="section-title mb-4">{{ $page->title }}</h1>
    <article class="card"><div class="card-body p-4 p-md-5 lh-lg">{!! $page->html !!}</div></article>
</div></div></div>
@endsection
