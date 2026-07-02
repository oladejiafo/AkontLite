<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GuestSession extends Model
{
    protected $fillable = [
        'token', 'device_id', 'ip_address',
        'last_active_at', 'expires_at',
        'migrated_to_user_id', 'migrated_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'expires_at'     => 'datetime',
        'migrated_at'    => 'datetime',
    ];

    public static function createNew(string $deviceId = null, string $ip = null): self
    {
        return self::create([
            'token'          => (string) Str::uuid(),
            'device_id'      => $deviceId,
            'ip_address'     => $ip,
            'last_active_at' => now(),
            'expires_at'     => now()->addDays(30),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isMigrated(): bool
    {
        return !is_null($this->migrated_at);
    }

    public function migratedUser()
    {
        return $this->belongsTo(User::class, 'migrated_to_user_id');
    }

    public function invoices()
    {
        return Invoice::where('guest_token', $this->token);
    }

    public function receipts()
    {
        return Receipt::where('guest_token', $this->token);
    }

    // public function touch(array $options = []): bool
    // {
    //     $this->last_active_at = now();
    //     return $this->save();
    // }

    public function touch($attribute = null): bool
    {
        $this->last_active_at = now();
        return $this->save();
    }
}