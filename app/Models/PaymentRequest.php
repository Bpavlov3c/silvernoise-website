<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    protected $fillable = [
        'report_id',
        'customer_id',
        'amount',
        'currency',
        'iban',
        'bank_name',
        'account_holder',
        'invoice_path',
        'invoice_url',
        'status',
        'admin_notes',
        'requested_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:4',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
