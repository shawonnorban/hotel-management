<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

/*
| Guest-facing site
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/{room}', [RoomController::class, 'show'])->whereNumber('room')->name('rooms.show');

Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/checkout/{booking}', [BookingController::class, 'checkout'])->name('booking.checkout');
    Route::post('/checkout/{booking}', [BookingController::class, 'pay'])->name('booking.pay');
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('booking.index');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('booking.show');
});

/*
| Staff back office
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:6,1');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/reservations', [Admin\ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{booking}', [Admin\ReservationController::class, 'show'])->name('reservations.show');
        Route::patch('/reservations/{booking}/status', [Admin\ReservationController::class, 'updateStatus'])->name('reservations.status');
        Route::get('/rooms', [Admin\RoomController::class, 'index'])->name('rooms.index');
    });
});
