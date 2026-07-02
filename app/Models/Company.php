<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'city', 'country',
        'vat_number', 'tax_number', 'registration_number',
        'logo_path', 'currency', 'timezone', 'country_standard', 'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users')
                    ->withPivot('role', 'invited_at', 'accepted_at', 'is_active')
                    ->withTimestamps();
    }

    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function owner()
    {
        return $this->users()->wherePivot('role', 'owner')->first();
    }
}