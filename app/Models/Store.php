<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Store extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'logo_url', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(Release::class, 'release_stores')
            ->withPivot('status', 'store_release_url', 'delivered_at', 'live_at');
    }
}
