<?php

namespace App\Http\Requests\AreaMonitorada;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAreaMonitoradaRequest extends FormRequest
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
        ];
    }
}
