<?php

namespace App\Http\Requests\Incendio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncendioRequest extends FormRequest
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
            'nivel_risco' => ['required', 'string', Rule::in(['alto', 'medio', 'baixo'])],
            'area_id' => ['nullable', 'uuid', 'exists:areas_monitoradas,id'],
            'local_critico_id' => ['nullable', 'uuid', 'exists:locais_criticos,id'],
            'deteccao_satelite_id' => ['nullable', 'uuid', 'exists:deteccoes_satelite,id'],
        ];
    }
}
