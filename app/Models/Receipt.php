<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Receipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'user_id','invoice_id', 'guest_token', 'type',
        'receipt_number', 'vendor_name', 'customer_name',
        'receipt_date', 'subtotal', 'tax_amount',
        'discount_amount', 'total_amount', 'currency',
        'tax_rate', 'image_path', 'ocr_confidence',
        'ocr_raw', 'category', 'notes', 'status',
        'einvoice_qr', 'einvoice_xml',
    ];

    protected $casts = [
        'receipt_date'    => 'date',
        'ocr_raw'         => 'array',
        'subtotal'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'ocr_confidence'  => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class)->orderBy('sort_order');
    }

    public function isIncoming(): bool
    {
        return $this->type === 'incoming';
    }

    public function isOutgoing(): bool
    {
        return $this->type === 'outgoing';
    }

    public function isGuest(): bool
    {
        return is_null($this->user_id) && !is_null($this->guest_token);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum(fn($item) => $item->unit_price * $item->quantity);
        $taxAmount = $this->items->sum('tax_amount');

        $this->subtotal     = $subtotal;
        $this->tax_amount   = $taxAmount;
        $this->total_amount = $subtotal + $taxAmount - $this->discount_amount;
        $this->save();
    }
}