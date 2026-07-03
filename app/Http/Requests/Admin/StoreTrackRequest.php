<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'disc_number'    => 'integer|min:1',
            'track_number'   => 'required|integer|min:1',
            'title'          => 'required|string|max:500',
            'title_version'  => 'nullable|string|max:255',
            'isrc'           => 'nullable|string|max:20|unique:tracks,isrc' . ($id ? ",{$id}" : ''),
            'audio_language' => 'nullable|string|max:10',
            'length'         => 'nullable|integer|min:1',
            'preview_start'  => 'nullable|integer|min:0',
            'explicit_lyrics'=> 'boolean',
            'publisher'      => 'nullable|string|max:255',
            'hfa_percentage' => 'nullable|numeric|min:0|max:100',
            'copyright_c'    => 'nullable|string|max:255',
            'copyright_p'    => 'nullable|string|max:255',

            'artist_ids'         => 'nullable|array',
            'artist_ids.*.id'    => 'required|exists:artists,id',
            'artist_ids.*.role'  => 'nullable|string|max:100',
            'artist_ids.*.order' => 'nullable|integer|min:0',

            'genre_ids'   => 'nullable|array',
            'genre_ids.*' => 'exists:genres,id',
        ];
    }
}
