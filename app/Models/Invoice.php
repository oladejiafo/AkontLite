<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{

    protected $fillable = [
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
    ];
    
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function guestToken()
    {
        return $this->belongsTo(GuestToken::class);
    }
    
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    
}
