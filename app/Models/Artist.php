<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Artist extends Model
{
    protected $fillable = ['name', 'slug'];

    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(Release::class, 'release_artists')
            ->withPivot('role', 'sort_order');
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'track_artists')
            ->withPivot('role', 'sort_order');
    }
}
