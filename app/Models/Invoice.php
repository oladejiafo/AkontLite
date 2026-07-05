<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // original fields
        'user_id',
        'invoice_number',
        'sender_company_name',
        'sender_company_email',
        'sender_company_phone',
        'sender_company_address',
        'sender_logo_path',
        'customer_id',
        'issue_date',
        'due_date',
        'footer_note',
        'total_amount',
        'currency',
        'status',
        // new fields added by migrations
        'company_id',
        'guest_token',
        'sequential_number',
        'einvoice_qr',
        'einvoice_xml',
        'einvoice_standard',
        'client_name',
        'client_email',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'notes',
        'paid_at',
        'title',
        'guest_token_id',
        'customer_name',
        'customer_email',
        'customer_address',
    ];

    protected $casts = [
        'issue_date'   => 'datetime',
        'due_date'     => 'datetime',
        'paid_at'      => 'datetime',
        'total_amount' => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }
}