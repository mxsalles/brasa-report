<?php

namespace App\Http\Requests\Brigada;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrigadaRequest extends FormRequest
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
            'tipo' => ['sometimes', 'string', 'max:100'],
            'latitude_atual' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude_atual' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'disponivel' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
