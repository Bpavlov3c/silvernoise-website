<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    protected $fillable = [
        'label_id',
        'customer_id',
        'name',
        'period_label',
        'period_start',
        'period_end',
        'report_date',
        'file_path',
        'file_url',
        'total_earnings',
        'currency',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start'   => 'date',
            'period_end'     => 'date',
            'report_date'    => 'date',
            'paid_at'        => 'datetime',
            'total_earnings' => 'decimal:4',
        ];
    }

    // --- Relationships ---

    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(ReportEarning::class);
    }

    public function paymentRequest(): HasOne
    {
        return $this->hasOne(PaymentRequest::class);
    }

    // --- Helpers ---

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'payment_requested';
    }

    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    public function getEarningsByPlatformAttribute(): array
    {
        return $this->earnings()
            ->selectRaw('platform, SUM(earnings) as total, SUM(streams) as streams')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }
}
