<?php

namespace App\Http\Requests\LocalCritico;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocalCriticoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:150'],
            'tipo' => ['sometimes', 'string', Rule::in(['residencia', 'escola', 'infraestrutura'])],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'descricao' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
