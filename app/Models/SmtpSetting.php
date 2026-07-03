<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SmtpSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'host',
        'port',
        'username',
        'password_enc',
        'encryption',
        'from_email',
        'from_name',
        'is_active',
        'updated_by',
    ];

    protected $hidden = ['password_enc'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_enc'] = Crypt::encryptString($value);
    }

    public function getPasswordAttribute(): string
    {
        return Crypt::decryptString($this->attributes['password_enc']);
    }
}
