<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GuestSessionService;
use App\Services\EInvoiceService;
use App\Services\OCRService;
use App\Services\ReceiptService;

use App\Services\PdfService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GuestSessionService::class);
        $this->app->singleton(EInvoiceService::class);
        $this->app->singleton(OCRService::class);
        $this->app->singleton(PdfService::class);

        $this->app->singleton(ReceiptService::class, function ($app) {
            return new ReceiptService(
                $app->make(OCRService::class),
                $app->make(EInvoiceService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
