<?php

use App\Admin\ResourceRegistry;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\Accounting;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomQrController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
| Guest-facing site
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/room/{room}', [RoomQrController::class, 'show'])->whereNumber('room')->name('room.show');
Route::post('/room/{room}/cleaning', [RoomQrController::class, 'requestCleaning'])->whereNumber('room')->middleware('throttle:6,1')->name('room.cleaning');
Route::get('/rooms/{room}', [RoomController::class, 'show'])->whereNumber('room')->name('rooms.show');

// Payment provider callbacks: public, verified server-side (CSRF-exempt, see bootstrap/app.php).
Route::match(['get', 'post'], '/payments/{driver}/return', [OnlinePaymentController::class, 'return'])->where('driver', 'stripe|paypal|sslcommerz')->name('payments.return');
Route::match(['get', 'post'], '/payments/{driver}/cancel', [OnlinePaymentController::class, 'cancel'])->where('driver', 'stripe|paypal|sslcommerz')->name('payments.cancel');
Route::post('/payments/{driver}/notify', [OnlinePaymentController::class, 'notify'])->where('driver', 'stripe|paypal|sslcommerz')->name('payments.notify');

Route::get('/gallery', [SiteController::class, 'gallery'])->name('gallery');
Route::post('/subscribe', [SiteController::class, 'subscribe'])->middleware('throttle:5,1')->name('subscribe');
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
    Route::post('/my-bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('booking.cancel');
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

        Route::middleware('can:roles.manage')->group(function () {
            Route::resource('roles', Admin\RoleController::class)->except('show');
        });

        Route::middleware('can:settings.manage')->group(function () {
            Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
            Route::put('/settings/mail', [Admin\SettingsController::class, 'updateMail'])->name('settings.mail');
            Route::post('/settings/mail-test', [Admin\SettingsController::class, 'testMail'])->name('settings.mail-test');
            Route::get('/whatsapp/settings', [Admin\WhatsAppController::class, 'settings'])->name('whatsapp.settings');
            Route::put('/whatsapp/settings', [Admin\WhatsAppController::class, 'update'])->name('whatsapp.update');
            Route::get('/whatsapp/messages', [Admin\WhatsAppController::class, 'messages'])->name('whatsapp.messages');
            Route::post('/whatsapp/messages', [Admin\WhatsAppController::class, 'send'])->name('whatsapp.send');
            Route::get('/settings/payment-gateways', [Admin\PaymentGatewayController::class, 'index'])->name('gateways.index');
            Route::put('/settings/payment-gateways/{driver}', [Admin\PaymentGatewayController::class, 'update'])->where('driver', 'stripe|paypal|sslcommerz')->name('gateways.update');
        });

        Route::middleware('can:reservations.view')->group(function () {
            Route::get('/advance-bookings', [Admin\AdvanceBookingController::class, 'index'])->name('advance.index');
        });
        Route::put('/advance-bookings/rule', [Admin\AdvanceBookingController::class, 'updateRule'])->middleware('can:settings.manage')->name('advance.rule');
        Route::post('/advance-bookings/{booking}/advance', [Admin\AdvanceBookingController::class, 'receive'])->middleware('can:reservations.payments')->name('advance.receive');

        Route::prefix('reservations')->name('reservations.')->controller(Admin\ReservationController::class)->group(function () {
            Route::middleware('can:reservations.create')->group(function () {
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/quote', 'quote')->name('quote');
                Route::get('/customers', 'customers')->name('customers');
            });
            Route::middleware('can:reservations.view')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{booking}', 'show')->name('show');
                Route::get('/{booking}/invoice', 'invoice')->name('invoice');
            });
            Route::middleware('can:reservations.edit')->group(function () {
                Route::get('/{booking}/edit', 'edit')->name('edit');
                Route::put('/{booking}', 'update')->name('update');
                Route::post('/{booking}/charges', 'storeCharge')->name('charges.store');
                Route::delete('/{booking}/charges/{charge}', 'destroyCharge')->name('charges.destroy');
                Route::post('/{booking}/guests', 'addGuest')->name('guests.store');
                Route::delete('/{booking}/guests/{guest}', 'removeGuest')->name('guests.destroy');
            });
            Route::middleware('can:reservations.status')->group(function () {
                Route::post('/{booking}/confirm', 'confirm')->name('confirm');
                Route::post('/{booking}/check-in', 'checkIn')->name('check-in');
                Route::post('/{booking}/check-out', 'checkOut')->name('check-out');
                Route::post('/{booking}/cancel', 'cancel')->name('cancel');
            });
            Route::middleware('can:reservations.payments')->group(function () {
                Route::post('/{booking}/payments', 'storePayment')->name('payments.store');
                Route::post('/{booking}/refunds', 'storeRefund')->name('refunds.store');
            });
        });

        Route::middleware('can:backup.manage')->prefix('backups')->name('backups.')->controller(Admin\BackupController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{name}', 'download')->where('name', 'backup-[0-9]{8}-[0-9]{6}\.sql\.gz')->name('download');
            Route::delete('/{name}', 'destroy')->where('name', 'backup-[0-9]{8}-[0-9]{6}\.sql\.gz')->name('destroy');
        });

        Route::prefix('reports')->name('reports.')->middleware('can:reports.view')->controller(Admin\ReportController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/bookings', 'bookings')->name('bookings');
            Route::get('/receipts', 'receipts')->name('receipts');
            Route::get('/occupancy', 'occupancy')->name('occupancy');
            Route::get('/purchases', 'purchases')->name('purchases');
            Route::get('/stock', 'stock')->name('stock');
        });

        Route::prefix('housekeeping')->name('housekeeping.')->group(function () {
            Route::middleware('can:hk-tasks.view')->group(function () {
                Route::get('/tasks', [Admin\Housekeeping\CleaningController::class, 'tasks'])->name('tasks');
                Route::get('/qr', [Admin\Housekeeping\CleaningController::class, 'qrList'])->name('qr');
            });
            Route::middleware('can:hk-tasks.manage')->group(function () {
                Route::get('/assign', [Admin\Housekeeping\CleaningController::class, 'assignForm'])->name('assign');
                Route::post('/assign', [Admin\Housekeeping\CleaningController::class, 'assign'])->name('assign.store');
                Route::post('/tasks/{task}/items/{item}', [Admin\Housekeeping\CleaningController::class, 'toggleItem'])->whereNumber(['task', 'item'])->name('tasks.item');
                Route::post('/tasks/{task}/{action}', [Admin\Housekeeping\CleaningController::class, 'transition'])->whereNumber('task')->whereIn('action', ['start', 'complete', 'inspect', 'cancel'])->name('tasks.transition');
            });
            Route::get('/report', [Admin\Housekeeping\CleaningController::class, 'report'])->middleware('can:hk-tasks.view')->name('report');
        });

        Route::prefix('hall')->group(function () {
            Route::middleware('can:hall-bookings.manage')->group(function () {
                Route::get('/bookings/create', [Admin\Hall\HallBookingController::class, 'create'])->name('hall-bookings.create');
                Route::post('/bookings', [Admin\Hall\HallBookingController::class, 'store'])->name('hall-bookings.store');
                Route::post('/bookings/{booking}/status', [Admin\Hall\HallBookingController::class, 'status'])->name('hall-bookings.status');
                Route::post('/bookings/{booking}/pay', [Admin\Hall\HallBookingController::class, 'pay'])->name('hall-bookings.pay');
            });
            Route::middleware('can:hall-bookings.view')->group(function () {
                Route::get('/bookings', [Admin\Hall\HallBookingController::class, 'index'])->name('hall-bookings.index');
                Route::get('/bookings/{booking}', [Admin\Hall\HallBookingController::class, 'show'])->whereNumber('booking')->name('hall-bookings.show');
                Route::get('/status', [Admin\Hall\HallBookingController::class, 'board'])->name('hall-bookings.board');
                Route::get('/report', [Admin\Hall\HallBookingController::class, 'report'])->name('hall-bookings.report');
            });
        });

        Route::prefix('laundry')->name('laundry.')->group(function () {
            Route::middleware('can:hk-laundry.manage')->group(function () {
                Route::get('/orders/create', [Admin\Housekeeping\LaundryController::class, 'create'])->name('create');
                Route::post('/orders', [Admin\Housekeeping\LaundryController::class, 'store'])->name('store');
                Route::post('/orders/{order}/status', [Admin\Housekeeping\LaundryController::class, 'status'])->name('status');
                Route::post('/orders/{order}/cancel', [Admin\Housekeeping\LaundryController::class, 'cancel'])->name('cancel');
                Route::post('/orders/{order}/pay', [Admin\Housekeeping\LaundryController::class, 'pay'])->name('pay');
            });
            Route::middleware('can:hk-laundry.view')->group(function () {
                Route::get('/orders', [Admin\Housekeeping\LaundryController::class, 'index'])->name('index');
                Route::get('/orders/{order}', [Admin\Housekeeping\LaundryController::class, 'show'])->whereNumber('order')->name('show');
                Route::get('/payments', [Admin\Housekeeping\LaundryController::class, 'payments'])->name('payments');
            });
        });

        Route::prefix('hr')->name('hr.')->group(function () {
            Route::get('/attendance', [Admin\Hr\AttendanceController::class, 'sheet'])->middleware('can:hr-attendance.manage')->name('attendance');
            Route::get('/attendance/report', [Admin\Hr\AttendanceController::class, 'report'])->middleware('can:hr-attendance.manage')->name('attendance.report');
            Route::middleware('can:hr-attendance.manage')->group(function () {
                Route::post('/attendance', [Admin\Hr\AttendanceController::class, 'save'])->name('attendance.save');
                Route::put('/attendance/weekly-off', [Admin\Hr\AttendanceController::class, 'weeklyOff'])->name('attendance.weekly-off');
            });
            Route::middleware('can:hr-roster.view')->group(function () {
                Route::get('/roster', [Admin\Hr\RosterController::class, 'index'])->name('roster.index');
                Route::get('/attendance-dashboard', [Admin\Hr\RosterController::class, 'dashboard'])->name('roster.dashboard');
            });
            Route::middleware('can:hr-roster.manage')->group(function () {
                Route::get('/roster/assign', [Admin\Hr\RosterController::class, 'assignForm'])->name('roster.assign');
                Route::post('/roster/assign', [Admin\Hr\RosterController::class, 'assign'])->name('roster.assign.store');
            });
            Route::get('/leave', [Admin\Hr\LeaveController::class, 'index'])->middleware('can:hr-leave.manage')->name('leave.index');
            Route::middleware('can:hr-leave.manage')->group(function () {
                Route::post('/leave', [Admin\Hr\LeaveController::class, 'store'])->name('leave.store');
                Route::post('/leave/{leaveRequest}/decide', [Admin\Hr\LeaveController::class, 'decide'])->name('leave.decide');
            });
            Route::middleware('can:hr-loans.manage')->group(function () {
                Route::get('/loans', [Admin\Hr\LoanController::class, 'index'])->name('loans.index');
                Route::post('/loans', [Admin\Hr\LoanController::class, 'store'])->name('loans.store');
            });
            Route::middleware('can:hr-payroll.view')->group(function () {
                Route::get('/payroll', [Admin\Hr\PayrollController::class, 'index'])->name('payroll.index');
                Route::get('/payroll/{run}', [Admin\Hr\PayrollController::class, 'show'])->whereNumber('run')->name('payroll.show');
                Route::get('/payroll/{run}/export', [Admin\Hr\PayrollController::class, 'export'])->whereNumber('run')->name('payroll.export');
                Route::get('/payroll/{run}/payslip/{item}', [Admin\Hr\PayrollController::class, 'payslip'])->whereNumber(['run', 'item'])->name('payroll.payslip');
                Route::get('/employees/{employee}/salary', [Admin\Hr\EmployeeController::class, 'salary'])->name('employees.salary');
            });
            Route::middleware('can:hr-payroll.run')->group(function () {
                Route::post('/payroll', [Admin\Hr\PayrollController::class, 'generate'])->name('payroll.generate');
                Route::post('/payroll/{run}/finalize', [Admin\Hr\PayrollController::class, 'finalize'])->name('payroll.finalize');
                Route::post('/payroll/{run}/reopen', [Admin\Hr\PayrollController::class, 'reopen'])->name('payroll.reopen');
                Route::post('/payroll/{run}/pay', [Admin\Hr\PayrollController::class, 'pay'])->name('payroll.pay');
                Route::delete('/payroll/{run}', [Admin\Hr\PayrollController::class, 'destroy'])->name('payroll.destroy');
                Route::put('/employees/{employee}/salary', [Admin\Hr\EmployeeController::class, 'updateSalary'])->name('employees.salary.update');
            });
            Route::middleware('can:hr-employees.view')->get('/employees/{employee}/profile', [Admin\Hr\EmployeeProfileController::class, 'show'])->name('employees.profile');
            Route::middleware('can:hr-employees.edit')->group(function () {
                Route::post('/employees/{employee}/records/{kind}', [Admin\Hr\EmployeeProfileController::class, 'store'])->name('employees.records.store');
                Route::delete('/employees/{employee}/records/{kind}/{record}', [Admin\Hr\EmployeeProfileController::class, 'destroy'])->whereNumber('record')->name('employees.records.destroy');
            });
            Route::post('/candidates/{candidate}/hire', [Admin\Hr\EmployeeController::class, 'hire'])->middleware('can:hr-employees.create')->name('candidates.hire');
        });

        Route::prefix('purchasing')->group(function () {
            Route::middleware('can:purchases.create')->group(function () {
                Route::get('/purchases/create', [Admin\PurchaseController::class, 'create'])->name('purchases.create');
                Route::post('/purchases', [Admin\PurchaseController::class, 'store'])->name('purchases.store');
                Route::post('/purchases/{purchase}/return', [Admin\PurchaseController::class, 'returnGoods'])->name('purchases.return');
            });
            Route::post('/purchases/{purchase}/pay', [Admin\PurchaseController::class, 'pay'])->middleware('can:purchases.pay')->name('purchases.pay');
            Route::middleware('can:purchases.view')->group(function () {
                Route::get('/purchases', [Admin\PurchaseController::class, 'index'])->name('purchases.index');
                Route::get('/returns', [Admin\PurchaseController::class, 'returns'])->name('returns.index');
                Route::get('/returns/{return}/invoice', [Admin\PurchaseController::class, 'returnInvoice'])->whereNumber('return')->name('returns.invoice');
                Route::get('/purchases/{purchase}', [Admin\PurchaseController::class, 'show'])->whereNumber('purchase')->name('purchases.show');
            });
            Route::middleware('can:stock.view')->group(function () {
                Route::get('/stock', [Admin\StockController::class, 'index'])->name('stock.index');
                Route::get('/stock/destroyed', [Admin\StockController::class, 'destroyed'])->name('stock.destroyed');
                Route::get('/stock/movements', [Admin\StockController::class, 'movements'])->name('stock.movements');
            });
            Route::middleware('can:stock.adjust')->group(function () {
                Route::post('/stock/issue', [Admin\StockController::class, 'issue'])->name('stock.issue');
                Route::post('/stock/waste', [Admin\StockController::class, 'waste'])->name('stock.waste');
                Route::post('/stock/adjust', [Admin\StockController::class, 'adjust'])->name('stock.adjust');
            });
        });

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
