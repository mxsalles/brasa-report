<?php

namespace App\Http\Requests\DeteccaoSatelite;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeteccaoSateliteRequest extends FormRequest
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'detectado_em' => ['required', 'date'],
            'confianca' => ['required', 'numeric', 'between:0,100'],
            'fonte' => ['nullable', 'string', 'max:100'],
        ];
    }
}
