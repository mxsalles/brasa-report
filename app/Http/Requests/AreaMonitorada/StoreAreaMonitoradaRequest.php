<?php

namespace App\Http\Requests\AreaMonitorada;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaMonitoradaRequest extends FormRequest
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
            'arquivo' => ['nullable', 'file', 'extensions:geojson,json,kml,zip,shp', 'max:51200'],
        ];
    }
}
