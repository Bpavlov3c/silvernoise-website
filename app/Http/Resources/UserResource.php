<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'surname'      => $this->surname,
            'full_name'    => $this->full_name,
            'display_name' => $this->display_name,
            'email'        => $this->email,
            'role'         => $this->role,
            'customer_type'=> $this->customer_type,
            'company_name' => $this->company_name,
            'is_active'    => $this->is_active,
            'is_blocked'   => $this->is_blocked,
            'featured'     => $this->featured,
            'contract_terminated_at' => $this->contract_terminated_at,
            'email_verified_at'      => $this->email_verified_at,
            'created_at'   => $this->created_at,
            'labels'       => $this->whenLoaded('labels', fn() =>
                $this->labels->map(fn($l) => [
                    'id'         => $l->id,
                    'name'       => $l->name,
                    'slug'       => $l->slug,
                    'is_primary' => $l->pivot->is_primary,
                ])
            ),
        ];
    }
}
