<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/tom-select/tom-select.complete.min.js') }}"></script>
<script>window.HOTEL_MONEY = @json(\App\Support\Money::jsConfig());</script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
