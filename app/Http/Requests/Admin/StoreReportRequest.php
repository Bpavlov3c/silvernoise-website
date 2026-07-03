<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label_id'       => 'required|exists:labels,id',
            'customer_id'    => 'required|exists:users,id',
            'name'           => 'required|string|max:255',
            'period_label'   => 'required|string|max:100',
            'period_start'   => 'required|date',
            'period_end'     => 'required|date|after_or_equal:period_start',
            'report_date'    => 'required|date',
            'total_earnings' => 'required|numeric|min:0',
            'currency'       => 'sometimes|string|size:3',
            'file'           => 'required|file|mimes:pdf|max:51200', // 50MB
        ];
    }
}
