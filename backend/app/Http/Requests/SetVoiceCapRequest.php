<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetVoiceCapRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'monthly_cap_cents.required' => 'Le plafond mensuel est obligatoire.',
            'monthly_cap_cents.integer' => 'Le plafond mensuel doit être un entier.',
            'monthly_cap_cents.min' => 'Le plafond mensuel ne peut pas être négatif.',
            'monthly_cap_cents.max' => 'Le plafond mensuel ne peut pas dépasser 100 €.',
            'auto_renew.required' => 'Le paramètre de renouvellement automatique est obligatoire.',
            'auto_renew.boolean' => 'Le paramètre de renouvellement automatique doit être booléen.',
        ];
    }
}
