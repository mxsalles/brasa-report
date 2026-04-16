<?php

namespace App\Http\Requests\DespachoBrigada;

use App\Models\DespachoBrigada;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FinalizarDespachoRequest extends FormRequest
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
            'finalizado_em' => ['required', 'date'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
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

            $finalizadoEm = $this->input('finalizado_em');

            if ($finalizadoEm === null) {
                return;
            }

            if ($despacho->chegada_em === null) {
                return;
            }

            if (strtotime((string) $finalizadoEm) < $despacho->chegada_em->getTimestamp()) {
                $validator->errors()->add(
                    'finalizado_em',
                    'O campo finalizado_em deve ser uma data posterior ou igual a chegada em.'
                );
            }
        });
    }
}
