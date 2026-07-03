<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'release_id'      => $this->release_id,
            'disc_number'     => $this->disc_number,
            'track_number'    => $this->track_number,
            'title'           => $this->title,
            'title_version'   => $this->title_version,
            'isrc'            => $this->isrc,
            'audio_language'  => $this->audio_language,
            'length'          => $this->length,
            'length_formatted'=> $this->length_formatted,
            'preview_start'   => $this->preview_start,
            'explicit_lyrics' => $this->explicit_lyrics,
            'publisher'       => $this->publisher,
            'hfa_percentage'  => $this->hfa_percentage,
            'copyright_c'     => $this->copyright_c,
            'copyright_p'     => $this->copyright_p,
            'audio_file_size' => $this->audio_file_size,
            'audio_file_size_formatted' => $this->file_size_formatted,
            'has_audio'       => ! is_null($this->audio_file_path),
            'created_at'      => $this->created_at,

            'artists' => $this->whenLoaded('artists', fn() =>
                $this->artists->map(fn($a) => [
                    'id'         => $a->id,
                    'name'       => $a->name,
                    'role'       => $a->pivot->role,
                    'is_primary' => (bool) $a->pivot->is_primary,
                ])
            ),

            'genres' => $this->whenLoaded('genres', fn() =>
                $this->genres->map(fn($g) => ['id' => $g->id, 'name' => $g->name])
            ),
        ];
    }
}
