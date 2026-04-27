<?php

namespace App\Http\Requests\Incendio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncendioRequest extends FormRequest
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
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'detectado_em' => ['sometimes', 'date'],
            'nivel_risco' => ['sometimes', 'string', Rule::in(['alto', 'medio', 'baixo'])],
            'area_id' => ['sometimes', 'nullable', 'uuid', 'exists:areas_monitoradas,id'],
            'local_critico_id' => ['sometimes', 'nullable', 'uuid', 'exists:locais_criticos,id'],
            'deteccao_satelite_id' => ['sometimes', 'nullable', 'uuid', 'exists:deteccoes_satelite,id'],
        ];
    }
}
