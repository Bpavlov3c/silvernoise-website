<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'subject_bg',
        'subject_en',
        'body_bg',
        'body_en',
        'variables',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['variables' => 'array'];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Render body for a given language, replacing variable placeholders.
     */
    public function render(string $lang, array $variables = []): array
    {
        $subject = $lang === 'bg' ? $this->subject_bg : $this->subject_en;
        $body    = $lang === 'bg' ? $this->body_bg    : $this->body_en;

        foreach ($variables as $key => $value) {
            $subject = str_replace("{{" . $key . "}}", $value, $subject);
            $body    = str_replace("{{" . $key . "}}", $value, $body);
        }

        return compact('subject', 'body');
    }
}
