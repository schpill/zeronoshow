<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetWhatsAppCapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monthly_cap_cents' => ['required', 'integer', 'min:0', 'max:10000'],
            'auto_renew' => ['required', 'boolean'],
        ];
    }
}
