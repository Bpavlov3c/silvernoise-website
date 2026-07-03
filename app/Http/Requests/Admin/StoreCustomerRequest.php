<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'          => 'required|string|max:100',
            'surname'       => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email' . ($id ? ",{$id}" : ''),
            'customer_type' => 'required|in:individual,company',
            'company_name'  => 'required_if:customer_type,company|nullable|string|max:255',
        ];
    }
}
