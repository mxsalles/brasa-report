<?php

namespace App\Http\Requests\Incendio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarRiscoRequest extends FormRequest
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
            'nivel_risco' => ['required', 'string', Rule::in(['alto', 'medio', 'baixo'])],
        ];
    }
}
