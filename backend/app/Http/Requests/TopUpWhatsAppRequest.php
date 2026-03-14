<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopUpWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'min:100', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_cents.min' => 'Le montant minimum est de 1 €.',
            'amount_cents.max' => 'Le montant maximum par rechargement est de 100 €.',
        ];
    }
}
