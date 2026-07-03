<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Label extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'logo_url'];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_labels', 'label_id', 'customer_id')
            ->withPivot('is_primary', 'assigned_at');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
