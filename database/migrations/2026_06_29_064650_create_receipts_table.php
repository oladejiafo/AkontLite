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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('guest_token')->nullable()->index();
            $table->enum('type', ['incoming', 'outgoing'])->default('outgoing');
            $table->string('receipt_number')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->date('receipt_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('image_path')->nullable();
            $table->float('ocr_confidence')->nullable();
            $table->json('ocr_raw')->nullable();
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'exported'])->default('draft');
            $table->string('einvoice_qr')->nullable();
            $table->text('einvoice_xml')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
