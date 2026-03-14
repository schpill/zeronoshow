<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicStoreWaitlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slot_date' => 'required|date|after_or_equal:today',
            'slot_time' => 'required|date_format:H:i',
            'client_name' => 'required|string|max:150',
            'client_phone' => 'required|string|regex:/^\+[1-9]\d{7,14}$/',
            'party_size' => 'required|integer|min:1|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'slot_date.required' => 'La date est requise.',
            'slot_time.required' => 'L\'heure est requise.',
            'client_name.required' => 'Votre nom est requis.',
            'client_phone.required' => 'Votre numéro de téléphone est requis.',
            'client_phone.regex' => 'Le format du téléphone n\'est pas valide (ex: +33612345678).',
            'party_size.required' => 'Le nombre de personnes est requis.',
        ];
    }
}
