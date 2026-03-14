<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Models\WidgetSetting;
use Illuminate\Foundation\Http\FormRequest;

class PublicStoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Business|null $business */
        $business = $this->route('business');
        /** @var WidgetSetting|null $widgetSetting */
        $widgetSetting = $business?->widgetSetting;
        $maxPartySize = $widgetSetting !== null ? $widgetSetting->max_party_size : 100;

        return [
            'guest_token' => ['required', 'string'],
            'party_size' => ['required', 'integer', 'min:1', 'max:'.$maxPartySize],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'guest_name' => ['required', 'string', 'max:100'],
            'guest_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_token.required' => 'Le token de réservation est obligatoire.',
            'party_size.required' => 'Le nombre de couverts est obligatoire.',
            'party_size.min' => 'Le nombre de couverts doit être au moins de 1.',
            'party_size.max' => 'Le nombre de couverts dépasse la capacité maximale autorisée.',
            'date.required' => 'La date est obligatoire.',
            'date.after_or_equal' => 'La date doit être aujourd\'hui ou ultérieure.',
            'time.required' => "L'heure est obligatoire.",
            'time.date_format' => "Le format de l'heure est invalide (HH:MM).",
            'guest_name.required' => 'Le nom est obligatoire.',
            'guest_name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'guest_phone.required' => 'Le numéro de téléphone est obligatoire.',
            'guest_phone.regex' => 'Le numéro doit être au format E.164.',
        ];
    }
}
