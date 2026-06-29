<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status', // pending, successful, failed
        'paid_at',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
        
}
