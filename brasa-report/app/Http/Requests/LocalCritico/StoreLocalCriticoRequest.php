<?php

namespace App\Http\Requests\LocalCritico;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocalCriticoRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:150'],
            'tipo' => ['required', 'string', Rule::in(['residencia', 'escola', 'infraestrutura'])],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'descricao' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
