<?php

namespace App\Http\Requests\LeituraMeteorologica;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeituraMeteorologicaRequest extends FormRequest
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
            'incendio_id' => ['prohibited'],
            'temperatura' => ['required', 'numeric', 'between:-50,60'],
            'umidade' => ['required', 'numeric', 'between:0,100'],
            'velocidade_vento' => ['required', 'numeric', 'min:0'],
            'registrado_em' => ['required', 'date'],
            'gera_alerta' => ['nullable', 'boolean'],
        ];
    }
}
