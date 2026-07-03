<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportEarning extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'platform',
        'country_code',
        'track_id',
        'streams',
        'earnings',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'streams'  => 'integer',
            'earnings' => 'decimal:4',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }
}
