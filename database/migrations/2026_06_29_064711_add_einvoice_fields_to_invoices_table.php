<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('sequential_number')->nullable()->after('invoice_number');
            $table->text('einvoice_qr')->nullable()->after('sequential_number');
            $table->text('einvoice_xml')->nullable()->after('einvoice_qr');
            $table->enum('einvoice_standard', ['ZATCA', 'FIRS', 'none'])
                ->default('none')
                ->after('einvoice_xml');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            //
        });
    }
};
