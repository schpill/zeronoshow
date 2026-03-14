<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWaitlistEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot_date.required' => 'La date du créneau est requise.',
            'slot_date.date' => 'La date du créneau n\'est pas valide.',
            'slot_date.after_or_equal' => 'La date du créneau ne peut pas être dans le passé.',
            'slot_time.required' => 'L\'heure du créneau est requise.',
            'slot_time.date_format' => 'L\'heure du créneau doit être au format H:i (ex: 19:30).',
            'client_name.required' => 'Le nom du client est requis.',
            'client_phone.required' => 'Le téléphone du client est requis.',
            'client_phone.regex' => 'Le format du téléphone doit être au format E.164 (ex: +33612345678).',
            'party_size.required' => 'Le nombre de personnes est requis.',
            'party_size.integer' => 'Le nombre de personnes doit être un entier.',
            'party_size.min' => 'Le nombre de personnes doit être au moins 1.',
            'party_size.max' => 'Le nombre de personnes ne peut pas dépasser 50.',
        ];
    }
}
