<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" href="{{ asset('assets/img/fav.png') }}">
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
<script>try{var t=localStorage.getItem('theme');if(t){document.documentElement.setAttribute('data-bs-theme',t)}}catch(e){}</script>
