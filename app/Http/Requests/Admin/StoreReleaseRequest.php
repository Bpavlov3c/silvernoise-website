<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreReleaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'label_id'              => 'required|exists:labels,id',
            'customer_id'           => 'required|exists:users,id',
            'title'                 => 'required|string|max:500',
            'title_version'         => 'nullable|string|max:255',
            'catalog_id'            => 'nullable|string|max:100|unique:releases,catalog_id' . ($id ? ",{$id}" : ''),
            'upc'                   => 'nullable|string|max:20|unique:releases,upc' . ($id ? ",{$id}" : ''),
            'status'                => 'sometimes|in:draft,pending,approved,delivered,live,takedown',
            'original_release_date' => 'nullable|date',
            'copyright_c'           => 'nullable|string|max:255',
            'copyright_p'           => 'nullable|string|max:255',
            'cover_art_url'         => 'nullable|url',
            'physical_distribution' => 'boolean',

            // Artist pivot data
            'artist_ids'            => 'nullable|array',
            'artist_ids.*.id'       => 'required|exists:artists,id',
            'artist_ids.*.role'     => 'nullable|string|max:100',
            'artist_ids.*.order'    => 'nullable|integer|min:0',

            // Genre IDs
            'genre_ids'             => 'nullable|array',
            'genre_ids.*'           => 'exists:genres,id',
        ];
    }
}
