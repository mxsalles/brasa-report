<?php

namespace App\Http\Requests\Brigada;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarLocalizacaoBrigadaRequest extends FormRequest
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
            'latitude_atual' => ['required', 'numeric', 'between:-90,90'],
            'longitude_atual' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
