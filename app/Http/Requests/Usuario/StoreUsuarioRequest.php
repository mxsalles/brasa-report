<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
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
            'email' => ['required', 'email', 'unique:usuarios,email'],
            'cpf' => ['required', 'string', 'size:11', 'unique:usuarios,cpf'],
            'senha' => ['required', 'string', 'min:8', 'confirmed'],
            'funcao' => ['required', 'in:brigadista,gestor,admin'],
            'brigada_id' => ['nullable', 'uuid', 'exists:brigadas,id'],
        ];
    }
}
