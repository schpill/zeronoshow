<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeoChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', Rule::in(['telegram', 'whatsapp', 'voice', 'sms', 'slack', 'discord'])],
            'external_identifier' => ['required', 'string', 'max:255'],
            'bot_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.required' => 'Le type de canal est obligatoire.',
            'channel.in' => 'Le type de canal est invalide.',
            'external_identifier.required' => 'L’identifiant externe est obligatoire.',
            'bot_name.max' => 'Le nom du bot ne peut pas depasser 100 caracteres.',
        ];
    }
}
