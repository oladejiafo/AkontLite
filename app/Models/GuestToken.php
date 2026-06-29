<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestToken extends Model
{
    protected $fillable = [
        'token',
    ];
    
    protected $casts = [];
    

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    
}
