<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users')
                    ->withPivot('role', 'invited_at', 'accepted_at', 'is_active')
                    ->withTimestamps();
    }

    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function ownedCompanies()
    {
        return $this->companies()->wherePivot('role', 'owner');
    }

    // public function activeCompany(): ?Company
    // {
    //     return $this->companies()
    //                 ->wherePivot('is_active', true)
    //                 ->latest('company_users.created_at')
    //                 ->first();
    // }

    public function roleInCompany(int $companyId): ?string
    {
        $cu = $this->companyUsers()
                ->where('company_id', $companyId)
                ->first();
        return $cu?->role;
    }

    public function activeCompany(): ?Company
    {
        try {
            return $this->companies()
                        ->wherePivot('is_active', true)
                        ->latest('company_users.created_at')
                        ->first();
        } catch (\Exception $e) {
            return null;
        }
    }
}
