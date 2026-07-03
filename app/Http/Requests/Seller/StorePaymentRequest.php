<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'report_id'      => 'required|exists:reports,id',
            'iban'           => 'required|string|max:50',
            'bank_name'      => 'nullable|string|max:255',
            'account_holder' => 'nullable|string|max:255',
            'invoice'        => 'nullable|file|mimes:pdf|max:10240', // 10MB
        ];
    }
}
