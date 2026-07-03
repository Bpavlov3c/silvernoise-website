<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    protected $fillable = [
        'label_id',
        'customer_id',
        'title',
        'title_version',
        'catalog_id',
        'upc',
        'status',
        'original_release_date',
        'copyright_c',
        'copyright_p',
        'cover_art_url',
        'cover_art_path',
        'physical_distribution',
        'kvz_id',
        'kvz_synced_at',
        'kvz_raw',
    ];

    protected function casts(): array
    {
        return [
            'original_release_date' => 'date',
            'kvz_synced_at'         => 'datetime',
            'physical_distribution' => 'boolean',
            'kvz_raw'               => 'array',
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

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class)->orderBy('disc_number')->orderBy('track_number');
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'release_artists')
            ->withPivot('role', 'is_primary', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'release_genres');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'release_stores')
            ->withPivot('status', 'store_release_url', 'delivered_at', 'live_at');
    }

    // --- Helpers ---

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isTakenDown(): bool
    {
        return $this->status === 'takedown';
    }

    public function isFromKvz(): bool
    {
        return ! is_null($this->kvz_id);
    }
}
