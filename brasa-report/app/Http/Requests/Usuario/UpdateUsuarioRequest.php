<?php

namespace App\Http\Requests\Usuario;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
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
        /** @var Usuario $usuario */
        $usuario = $this->route('usuario');

        return [
            'nome' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'cpf' => ['sometimes', 'string', 'size:11', Rule::unique('usuarios', 'cpf')->ignore($usuario->id)],
            'brigada_id' => ['sometimes', 'nullable', 'uuid', 'exists:brigadas,id'],
        ];
    }
}
