<?php

namespace App\Http\Requests\DespachoBrigada;

use App\Models\DespachoBrigada;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegistrarChegadaRequest extends FormRequest
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
            'chegada_em' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var DespachoBrigada|null $despacho */
            $despacho = $this->route('despacho');

            if ($despacho === null || ! $despacho instanceof DespachoBrigada) {
                return;
            }

            $chegadaEm = $this->input('chegada_em');

            if ($chegadaEm === null) {
                return;
            }

            if ($despacho->despachado_em !== null && strtotime((string) $chegadaEm) < $despacho->despachado_em->getTimestamp()) {
                $validator->errors()->add(
                    'chegada_em',
                    'O campo chegada_em deve ser uma data posterior ou igual a despachado em.'
                );
            }
        });
    }
}
