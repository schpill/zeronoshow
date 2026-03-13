<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'scheduled_at' => ['required', 'date', 'after:'.now()->subMinutes(5)->toIso8601String()],
            'guests' => ['nullable', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'phone_verified' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Le nom du client est obligatoire.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => 'Le numéro doit être au format E.164.',
            'scheduled_at.required' => 'La date du rendez-vous est obligatoire.',
            'scheduled_at.after' => 'La date du rendez-vous doit être dans le futur.',
            'guests.min' => 'Le nombre de couverts doit être au moins de 1.',
            'guests.max' => 'Le nombre de couverts ne peut pas dépasser 100.',
            'notes.max' => 'Les notes ne peuvent pas dépasser 1000 caractères.',
        ];
    }
}
