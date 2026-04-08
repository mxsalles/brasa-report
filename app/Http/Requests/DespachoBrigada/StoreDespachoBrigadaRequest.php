<?php

namespace App\Http\Requests\DespachoBrigada;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDespachoBrigadaRequest extends FormRequest
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
            'despachado_em' => ['prohibited'],
            'brigada_id' => ['required', 'uuid', Rule::exists('brigadas', 'id')],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
