<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'title_version'         => $this->title_version,
            'catalog_id'            => $this->catalog_id,
            'upc'                   => $this->upc,
            'status'                => $this->status,
            'original_release_date' => $this->original_release_date?->format('Y-m-d'),
            'copyright_c'           => $this->copyright_c,
            'copyright_p'           => $this->copyright_p,
            'cover_art_url'         => $this->cover_art_url,
            'physical_distribution' => $this->physical_distribution,
            'kvz_id'                => $this->kvz_id,
            'kvz_synced_at'         => $this->kvz_synced_at,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,

            'label' => $this->whenLoaded('label', fn() => [
                'id'   => $this->label->id,
                'name' => $this->label->name,
            ]),

            'customer' => $this->whenLoaded('customer', fn() => [
                'id'           => $this->customer->id,
                'display_name' => $this->customer->display_name,
            ]),

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

            'tracks' => $this->whenLoaded('tracks', fn() =>
                TrackResource::collection($this->tracks)
            ),

            'stores' => $this->whenLoaded('stores', fn() =>
                $this->stores->map(fn($s) => [
                    'id'                => $s->id,
                    'name'              => $s->name,
                    'status'            => $s->pivot->status,
                    'store_release_url' => $s->pivot->store_release_url,
                    'delivered_at'      => $s->pivot->delivered_at,
                    'live_at'           => $s->pivot->live_at,
                ])
            ),
        ];
    }
}
