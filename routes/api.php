<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Existing Controllers (kept for backward compat) ───
use App\Http\Controllers\GuestSessionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\AuthController;
// ─── New Api\ Controllers ───
use App\Http\Controllers\Api\GuestSessionController as ApiGuestSessionController;
use App\Http\Controllers\Api\InvoiceController as ApiInvoiceController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\OCRController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\ClientController;

// ─────────────────────────────────────────────────────
// Current user (Sanctum)
// ─────────────────────────────────────────────────────
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// ─────────────────────────────────────────────────────
// Auth (mobile)
// ─────────────────────────────────────────────────────
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    [AuthController::class, 'me']);
});

// ─────────────────────────────────────────────────────
// LEGACY routes (kept to avoid breaking existing mobile
// until fully migrated to new Api\ controllers)
// ─────────────────────────────────────────────────────
Route::post('/guest-session', [GuestSessionController::class, 'createSession']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('settings/update', [SettingsController::class, 'update']);
    Route::post('settings/logo', [SettingsController::class, 'uploadLogo']);

    Route::post('payment/link/{invoice_id}', [PaymentController::class, 'generateLink']);
    Route::post('payment/webhook', [PaymentController::class, 'handleWebhook']);
});

// ─────────────────────────────────────────────────────
// NEW: Guest session management
// ─────────────────────────────────────────────────────
Route::post('/guest/session', [ApiGuestSessionController::class, 'create']);

// ─────────────────────────────────────────────────────
// NEW: OCR (guest + auth, no middleware needed)
// ─────────────────────────────────────────────────────
Route::post('/ocr/extract', [OCRController::class, 'extract']);
Route::post('/ocr/scan-and-save', [OCRController::class, 'scanAndSave']);

// ─────────────────────────────────────────────────────
// NEW: Invoices (open for guest + auth access)
// ─────────────────────────────────────────────────────
Route::post('/invoices', [ApiInvoiceController::class, 'store']);
Route::get('/invoices/{id}', [ApiInvoiceController::class, 'show']);

// ─────────────────────────────────────────────────────
// NEW: Receipts (open for guest + auth access)
// ─────────────────────────────────────────────────────
Route::post('/receipts', [ReceiptController::class, 'store']);
Route::get('/receipts/{id}', [ReceiptController::class, 'show']);

// ─────────────────────────────────────────────────────
// NEW: Authenticated routes
// ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // guest migration after signup
    Route::post('/guest/migrate', [ApiGuestSessionController::class, 'migrate']);



Route::get('/clients',         [ClientController::class, 'index']);
Route::post('/clients',        [ClientController::class, 'store']);
Route::get('/clients/{id}',    [ClientController::class, 'show']);
Route::put('/clients/{id}',    [ClientController::class, 'update']);
Route::delete('/clients/{id}', [ClientController::class, 'destroy']);

    // invoices (auth)
    Route::get('/invoices', [ApiInvoiceController::class, 'index']);
    Route::put('/invoices/{id}', [ApiInvoiceController::class, 'update']);
    Route::delete('/invoices/{id}', [ApiInvoiceController::class, 'destroy']);

    // legacy invoice actions (kept until migrated)
    Route::post('invoices/{id}/send-email', [GuestSessionController::class, 'sendByEmail']);
    Route::post('invoices/{id}/reminder', [GuestSessionController::class, 'sendReminder']);
    Route::post('invoices/{id}/mark-paid', [GuestSessionController::class, 'markAsPaid']);
    Route::get('invoices/{id}/download', [GuestSessionController::class, 'downloadPdf']);

    // receipts (auth)
    Route::get('/receipts', [ReceiptController::class, 'index']);
    Route::put('/receipts/{id}', [ReceiptController::class, 'update']);
    Route::delete('/receipts/{id}', [ReceiptController::class, 'destroy']);

    // company
    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'show']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::put('/', [CompanyController::class, 'update']);
        Route::get('/members', [CompanyController::class, 'members']);
        Route::post('/invite', [CompanyController::class, 'invite']);
        Route::put('/members/{id}', [CompanyController::class, 'updateMember']);
        Route::delete('/members/{id}', [CompanyController::class, 'removeMember']);
        Route::post('/invitations/accept', [CompanyController::class, 'acceptInvitation']);
        Route::post('/logo', [CompanyController::class, 'uploadLogo']);
    });

    // reports
    Route::prefix('reports')->group(function () {
        Route::get('/summary', [ReportController::class, 'summary']);
        Route::get('/vat', [ReportController::class, 'vat']);
        Route::get('/export', [ReportController::class, 'export']);
    });

    // PDF generation
    Route::get('/invoices/{id}/pdf', [PdfController::class, 'invoicePdf']);
    Route::get('/receipts/{id}/pdf', [PdfController::class, 'receiptPdf']);
});