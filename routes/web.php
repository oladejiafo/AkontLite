<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestSessionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;

use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// routes/web.php
// Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [RegisteredUserController::class, 'register'])->name('register');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

require __DIR__.'/auth.php';

Route::get('/invoice/create', [InvoiceController::class, 'create'])->name('invoices.create');

Route::get('/invoice/recover-draft', [InvoiceController::class, 'recoverDraft'])->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/invoices/index', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');

    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');

    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])
        ->name('invoices.email');

    Route::post('/invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markPaid'])
         ->name('invoices.mark-as-paid');

    //Payments
    Route::get('/payments', [InvoiceController::class, 'paymentsIndex'])->name('payments.index');
    Route::get('/payments/create/{saleId?}', [InvoiceController::class, 'paymentsCreate'])->name('payments.create');
    Route::post('/payments', [InvoiceController::class, 'paymentsStore'])->name('payments.store');
    Route::get('/payments/{payment}/edit', [InvoiceController::class, 'paymentsEdit'])->name('payments.edit');
    Route::put('/payments/{payment}', [InvoiceController::class, 'paymentsUpdate'])->name('payments.update');
    Route::delete('/payments/{payment}', [InvoiceController::class, 'paymentsDestroy'])->name('payments.destroy');
    Route::get('/payments/{id}', [InvoiceController::class, 'paymentShow'])->name('payments.show');
    Route::post('/payments/{payment}/generate-invoice', [PaymentController::class, 'generateInvoiceForPayment'])
    ->name('payments.generate-invoice');
});

Route::prefix('invoices')->group(function () 
{   
    Route::get('/invoice/{invoice}/edit', [GuestSessionController::class, 'invoices.edit']);
    Route::post('/invoice/{invoice}', [GuestSessionController::class, 'updateGuestInvoice']);
    Route::get('/invoice/{invoice}', [GuestSessionController::class, 'showGuestInvoice']);
    Route::get('/invoice/{invoice}/download', [GuestSessionController::class, 'downloadGuestInvoice']);
});

Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy-policy', 'pages.policy')->name('policy');
Route::view('/support', 'pages.support')->name('support');
Route::get('/go-pro', function() {
    return view('marketing.go-pro');
 })->name('go.pro');
 