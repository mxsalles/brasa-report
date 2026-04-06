<?php

namespace App\Http\Requests\Brigada;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrigadaRequest extends FormRequest
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
            'tipo' => ['required', 'string', 'max:100'],
            'latitude_atual' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_atual' => ['nullable', 'numeric', 'between:-180,180'],
            'disponivel' => ['nullable', 'boolean'],
        ];
    }
}
