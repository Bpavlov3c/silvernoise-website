<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Track extends Model
{
    protected $fillable = [
        'release_id',
        'disc_number',
        'track_number',
        'title',
        'title_version',
        'isrc',
        'audio_language',
        'length',
        'preview_start',
        'explicit_lyrics',
        'publisher',
        'hfa_percentage',
        'copyright_c',
        'copyright_p',
        'audio_file_path',
        'audio_file_size',
    ];

    protected function casts(): array
    {
        return [
            'explicit_lyrics'  => 'boolean',
            'hfa_percentage'   => 'decimal:2',
            'audio_file_size'  => 'integer',
            'length'           => 'integer',
            'preview_start'    => 'integer',
            'disc_number'      => 'integer',
            'track_number'     => 'integer',
        ];
    }

    // --- Relationships ---

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'track_artists')
            ->withPivot('role', 'is_primary', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'track_genres');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(ReportEarning::class);
    }

    // --- Helpers ---

    public function getLengthFormattedAttribute(): string
    {
        if (! $this->length) {
            return '0:00';
        }
        $minutes = intdiv($this->length, 60);
        $seconds = $this->length % 60;
        return "{$minutes}:" . str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (! $this->audio_file_size) {
            return '—';
        }
        $mb = $this->audio_file_size / 1048576;
        return number_format($mb, 1) . ' MB';
    }
}
