<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug'];

    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(Release::class, 'release_genres');
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'track_genres');
    }
}
