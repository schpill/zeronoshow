<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VoiceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auto_call_enabled' => ['required', 'boolean'],
            'score_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'min_party_size' => ['nullable', 'integer', 'min:2', 'max:50'],
            'retry_count' => ['required', 'integer', 'min:0', 'max:5'],
            'retry_delay_minutes' => ['required', 'integer', 'min:5', 'max:120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('auto_call_enabled')) {
                    return;
                }

                if ($this->input('score_threshold') !== null || $this->input('min_party_size') !== null) {
                    return;
                }

                $message = 'Activez au moins un critère automatique: score ou taille de groupe.';
                $validator->errors()->add('auto_call_enabled', $message);
                $validator->errors()->add('score_threshold', $message);
            },
        ];
    }

    public function messages(): array
    {
        return [
            'auto_call_enabled.required' => 'L’activation des appels automatiques est obligatoire.',
            'auto_call_enabled.boolean' => 'Le paramètre d’activation doit être booléen.',
            'score_threshold.integer' => 'Le seuil de score doit être un entier.',
            'score_threshold.min' => 'Le seuil de score minimum est 0.',
            'score_threshold.max' => 'Le seuil de score maximum est 100.',
            'min_party_size.integer' => 'La taille minimale du groupe doit être un entier.',
            'min_party_size.min' => 'La taille minimale du groupe est 2.',
            'min_party_size.max' => 'La taille minimale du groupe ne peut pas dépasser 50.',
            'retry_count.required' => 'Le nombre de tentatives est obligatoire.',
            'retry_count.integer' => 'Le nombre de tentatives doit être un entier.',
            'retry_count.min' => 'Le nombre de tentatives ne peut pas être négatif.',
            'retry_count.max' => 'Le nombre de tentatives maximum est 5.',
            'retry_delay_minutes.required' => 'Le délai entre les tentatives est obligatoire.',
            'retry_delay_minutes.integer' => 'Le délai entre les tentatives doit être un entier.',
            'retry_delay_minutes.min' => 'Le délai minimum est de 5 minutes.',
            'retry_delay_minutes.max' => 'Le délai maximum est de 120 minutes.',
        ];
    }
}
