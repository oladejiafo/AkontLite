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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // For logged-in users
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guest_token_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('title')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue'])->default('draft');
            $table->string('currency')->default('USD');

            $table->string('sender_company_name')->nullable();
            $table->string('sender_company_email')->nullable();
            $table->string('sender_company_phone')->nullable();
            $table->text('sender_company_address')->nullable();
            $table->string('sender_logo_path')->nullable();
            $table->text('footer_note')->nullable();

            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
