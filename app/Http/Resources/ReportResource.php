<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'period_label'   => $this->period_label,
            'period_start'   => $this->period_start?->format('Y-m-d'),
            'period_end'     => $this->period_end?->format('Y-m-d'),
            'report_date'    => $this->report_date?->format('Y-m-d'),
            'total_earnings' => (float) $this->total_earnings,
            'currency'       => $this->currency,
            'status'         => $this->status,
            'paid_at'        => $this->paid_at,
            'created_at'     => $this->created_at,

            'label' => $this->whenLoaded('label', fn() => [
                'id'   => $this->label->id,
                'name' => $this->label->name,
            ]),

            'customer' => $this->whenLoaded('customer', fn() => [
                'id'           => $this->customer->id,
                'display_name' => $this->customer->display_name,
                'email'        => $this->customer->email,
            ]),

            'earnings_by_platform' => $this->whenLoaded('earnings', fn() =>
                $this->earnings
                    ->groupBy('platform')
                    ->map(fn($rows, $platform) => [
                        'platform' => $platform,
                        'streams'  => $rows->sum('streams'),
                        'earnings' => (float) $rows->sum('earnings'),
                    ])
                    ->values()
            ),

            'payment_request' => $this->whenLoaded('paymentRequest', fn() =>
                $this->paymentRequest ? new PaymentRequestResource($this->paymentRequest) : null
            ),
        ];
    }
}
