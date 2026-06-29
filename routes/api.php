<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestSessionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PaymentController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/invoice/create', [GuestSessionController::class, 'invoices.create']);
Route::prefix('api')->group(function () {
    // Route::get('/invoice/create', [GuestSessionController::class, 'invoices.create']);
    Route::post('/guest-session', [GuestSessionController::class, 'createSession']);
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{id}/send-email', [InvoiceController::class, 'sendByEmail']);
    Route::post('invoices/{id}/reminder', [InvoiceController::class, 'sendReminder']);
    Route::post('invoices/{id}/mark-paid', [InvoiceController::class, 'markAsPaid']);
    Route::get('invoices/{id}/download', [InvoiceController::class, 'downloadPdf']);

    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('settings/update', [SettingsController::class, 'update']);
    Route::post('settings/logo', [SettingsController::class, 'uploadLogo']);

    Route::post('payment/link/{invoice_id}', [PaymentController::class, 'generateLink']);
    Route::post('payment/webhook', [PaymentController::class, 'handleWebhook']);
});
