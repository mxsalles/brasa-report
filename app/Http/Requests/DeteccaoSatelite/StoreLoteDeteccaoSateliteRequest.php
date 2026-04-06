<?php

namespace App\Http\Requests\DeteccaoSatelite;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoteDeteccaoSateliteRequest extends FormRequest
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
            'deteccoes' => ['required', 'array', 'min:1', 'max:500'],
            'deteccoes.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'deteccoes.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'deteccoes.*.detectado_em' => ['required', 'date'],
            'deteccoes.*.confianca' => ['required', 'numeric', 'between:0,100'],
            'deteccoes.*.fonte' => ['nullable', 'string', 'max:100'],
        ];
    }
}
