<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'role',
        'customer_type',
        'company_name',
        'is_active',
        'is_blocked',
        'contract_terminated_at',
        'featured',
        'activation_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'activation_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'contract_terminated_at' => 'datetime',
            'is_active'              => 'boolean',
            'is_blocked'             => 'boolean',
            'featured'               => 'boolean',
            'password'               => 'hashed',
        ];
    }

    // --- Relationships ---

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'customer_labels', 'customer_id', 'label_id')
            ->withPivot('is_primary', 'assigned_at')
            ->withTimestamps('assigned_at', 'assigned_at');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class, 'customer_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'customer_id');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'customer_id');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    // --- Helpers ---

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    public function canAccess(): bool
    {
        return $this->is_active && ! $this->is_blocked && ! $this->contract_terminated_at;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} {$this->surname}";
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?? $this->full_name;
    }
}
