<?php

use App\Admin\ResourceRegistry;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\Accounting;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

/*
| Guest-facing site
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/{room}', [RoomController::class, 'show'])->whereNumber('room')->name('rooms.show');

Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,1')->name('contact.send');

Route::middleware('guest:customer')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->defaults('broker', 'customers')->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->defaults('broker', 'customers')->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->defaults('broker', 'customers')->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->defaults('broker', 'customers')->name('password.update');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/checkout/{booking}', [BookingController::class, 'checkout'])->name('booking.checkout');
    Route::post('/checkout/{booking}', [BookingController::class, 'pay'])->name('booking.pay');
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('booking.index');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('booking.show');
    Route::get('/my-bookings/{booking}/invoice', [BookingController::class, 'invoice'])->name('booking.invoice');
});

/*
| Staff back office
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::get('/forgot-password', [PasswordResetController::class, 'request'])->defaults('broker', 'admins')->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'email'])->defaults('broker', 'admins')->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->defaults('broker', 'admins')->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->defaults('broker', 'admins')->name('password.update');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

        Route::get('/', [Admin\DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');

        Route::middleware('can:reservations.view')->group(function () {
            Route::get('/reservations', [Admin\ReservationController::class, 'index'])->name('reservations.index');
            Route::get('/reservations/{booking}', [Admin\ReservationController::class, 'show'])->name('reservations.show');
        });
        Route::patch('/reservations/{booking}/status', [Admin\ReservationController::class, 'updateStatus'])
            ->middleware('can:reservations.status')->name('reservations.status');

        Route::prefix('accounting')->group(function () {
            Route::middleware('can:accounts.view')->group(function () {
                Route::get('/vouchers', [Accounting\VoucherController::class, 'index'])->name('vouchers.index');
                Route::get('/ledger', [Accounting\AccountingReportController::class, 'ledger'])->name('accounting.ledger');
                Route::get('/cash-book', [Accounting\AccountingReportController::class, 'cashBook'])->name('accounting.cash-book');
                Route::get('/trial-balance', [Accounting\AccountingReportController::class, 'trialBalance'])->name('accounting.trial-balance');
                Route::get('/income-statement', [Accounting\AccountingReportController::class, 'incomeStatement'])->name('accounting.income-statement');
                Route::get('/balance-sheet', [Accounting\AccountingReportController::class, 'balanceSheet'])->name('accounting.balance-sheet');
            });
            Route::middleware('can:accounts.manage')->group(function () {
                Route::get('/vouchers/create', [Accounting\VoucherController::class, 'create'])->name('vouchers.create');
                Route::post('/vouchers', [Accounting\VoucherController::class, 'store'])->name('vouchers.store');
                Route::post('/vouchers/{voucher}/void', [Accounting\VoucherController::class, 'void'])->name('vouchers.void');
            });
            Route::get('/vouchers/{voucher}', [Accounting\VoucherController::class, 'show'])->middleware('can:accounts.view')->whereNumber('voucher')->name('vouchers.show');
        });

        // Declarative master-data screens (see app/Admin/Resources). Keep last: the slug list is the only constraint.
        Route::controller(Admin\ResourceController::class)->group(function () {
            $slugs = implode('|', array_map('preg_quote', ResourceRegistry::slugs()));
            Route::get('/{resource}', 'index')->where('resource', $slugs)->name('resource.index');
            Route::get('/{resource}/create', 'create')->where('resource', $slugs)->name('resource.create');
            Route::post('/{resource}', 'store')->where('resource', $slugs)->name('resource.store');
            Route::get('/{resource}/{id}/edit', 'edit')->where('resource', $slugs)->name('resource.edit');
            Route::put('/{resource}/{id}', 'update')->where('resource', $slugs)->name('resource.update');
            Route::delete('/{resource}/{id}', 'destroy')->where('resource', $slugs)->name('resource.destroy');
        });
    });
});
