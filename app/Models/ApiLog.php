<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source',
        'endpoint',
        'method',
        'status_code',
        'response_time_ms',
        'records_synced',
        'error_message',
        'triggered_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
