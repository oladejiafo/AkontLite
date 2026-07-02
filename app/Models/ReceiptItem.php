<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    protected $fillable = [
        'receipt_id', 'description', 'quantity',
        'unit_price', 'tax_rate', 'tax_amount',
        'total', 'sort_order',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    protected static function booted(): void
    {
        static::saving(function (ReceiptItem $item) {
            $item->tax_amount = ($item->unit_price * $item->quantity) * ($item->tax_rate / 100);
            $item->total      = ($item->unit_price * $item->quantity) + $item->tax_amount;
        });
    }
}