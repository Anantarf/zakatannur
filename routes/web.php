<?php

use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\PeriodSettingsController;
use App\Http\Controllers\Internal\TemplateController;
use App\Http\Controllers\Internal\AuditLogController;
use App\Http\Controllers\Internal\TransactionAnomalyController;
use App\Http\Controllers\Internal\TransactionHistoryController;
use App\Http\Controllers\Internal\UserManagementController;
use App\Http\Controllers\Internal\MuzakkiController;
use App\Http\Controllers\Internal\ZakatTransactionController;
use App\Http\Controllers\Internal\ExportController;
use App\Http\Controllers\Internal\ProfileController;
use App\Http\Controllers\Guest\GuestPagesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GuestPagesController::class, 'home'])
    ->middleware(['throttle:public-summary'])
    ->name('home');

Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth', 'role:staff,admin,super_admin'])
    ->name('dashboard');

Route::middleware(['auth', 'role:staff,admin,super_admin'])
    ->name('internal.')
    ->prefix('internal')
    ->group(function () {


        Route::middleware(['role:admin,super_admin'])->group(function () {
            Route::get('/transactions/trash', [TransactionHistoryController::class, 'trash'])->name('transactions.trash');
            Route::post('/transactions/{transaction}/trash', [TransactionHistoryController::class, 'destroy'])->name('transactions.destroy');
            Route::post('/transactions/{transaction}/restore', [TransactionHistoryController::class, 'restore'])->withTrashed()->name('transactions.restore');
            Route::delete('/transactions/{transaction}/force-delete', [TransactionHistoryController::class, 'forceDelete'])->withTrashed()->name('transactions.force_delete');
            Route::get('/anomalies', [TransactionAnomalyController::class, 'index'])->name('anomalies.index');
            Route::get('/anomalies/{noTransaksi}', [TransactionAnomalyController::class, 'show'])->name('anomalies.show');
            Route::patch('/anomalies/{noTransaksi}/review-status', [TransactionAnomalyController::class, 'updateReviewStatus'])->name('anomalies.review_status');

            Route::get('/muzakki/trash', [MuzakkiController::class, 'trash'])->name('muzakki.trash');
            Route::post('/muzakki/{muzakki}/merge', [MuzakkiController::class, 'merge'])->name('muzakki.merge');
            Route::post('/muzakki/{muzakki}/restore', [MuzakkiController::class, 'restore'])->withTrashed()->name('muzakki.restore');
            Route::delete('/muzakki/{muzakki}/force-delete', [MuzakkiController::class, 'forceDelete'])->withTrashed()->name('muzakki.force_delete');

            // Excel Exports
            Route::get('/rekap/export/daily', [ExportController::class, 'exportDaily'])->name('rekap.export.daily');
            Route::get('/rekap/export/yearly', [ExportController::class, 'exportYearly'])->name('rekap.export.yearly');
        });

        Route::resource('muzakki', MuzakkiController::class)
            ->only(['index', 'show', 'edit', 'update', 'destroy'])
            ->names('muzakki');
        Route::get('/muzakki-autocomplete', [MuzakkiController::class, 'autocomplete'])->name('muzakki.autocomplete');
        Route::get('/history', [TransactionHistoryController::class, 'index'])->name('transactions.index');
        
        // Internal transactions
        Route::get('/transactions/create', [ZakatTransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [ZakatTransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}', [ZakatTransactionController::class, 'show'])->withTrashed()->name('transactions.show');
        Route::get('/transactions/{transaction}/edit', [ZakatTransactionController::class, 'edit'])->name('transactions.edit');
        Route::patch('/transactions/{transaction}', [ZakatTransactionController::class, 'update'])->name('transactions.update');
        Route::get('/transactions/{transaction}/receipt', [ZakatTransactionController::class, 'receipt'])->withTrashed()->name('transactions.receipt');

        Route::middleware(['role:admin,super_admin'])->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit_logs.index');
        });

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::middleware(['role:super_admin'])->group(function () {
            Route::get('/settings/period', [PeriodSettingsController::class, 'edit'])->name('settings.period.edit');
            Route::post('/settings/period', [PeriodSettingsController::class, 'update'])->name('settings.period.update');
            Route::post('/settings/period/start-new', [PeriodSettingsController::class, 'startNewPeriod'])->name('settings.period.startNew');

            Route::get('/templates/letterhead', [TemplateController::class, 'index'])->name('templates.letterhead');
            Route::post('/templates/letterhead', [TemplateController::class, 'store'])->name('templates.letterhead.store');
            Route::post('/templates/{template}/activate', [TemplateController::class, 'activate'])->name('templates.activate');
            Route::get('/templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');
            Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
        });
    });

require __DIR__.'/auth.php';
