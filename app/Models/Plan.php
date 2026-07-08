<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'currency',
        'remove_watermark', 'logo_upload', 'payment_gateways',
    ];

    protected $casts = [
        'remove_watermark' => 'boolean',
        'logo_upload' => 'boolean',
        'payment_gateways' => 'boolean',
    ];
}