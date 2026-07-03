<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'amount'         => (float) $this->amount,
            'currency'       => $this->currency,
            'iban'           => $this->iban,
            'bank_name'      => $this->bank_name,
            'account_holder' => $this->account_holder,
            'has_invoice'    => ! is_null($this->invoice_path),
            'invoice_url'    => $this->invoice_url,
            'status'         => $this->status,
            'admin_notes'    => $this->admin_notes,
            'requested_at'   => $this->requested_at,
            'processed_at'   => $this->processed_at,

            'customer' => $this->whenLoaded('customer', fn() => [
                'id'           => $this->customer->id,
                'display_name' => $this->customer->display_name,
                'email'        => $this->customer->email,
            ]),

            'report' => $this->whenLoaded('report', fn() => [
                'id'           => $this->report->id,
                'name'         => $this->report->name,
                'period_label' => $this->report->period_label,
                'label_name'   => $this->report->label?->name,
            ]),
        ];
    }
}
