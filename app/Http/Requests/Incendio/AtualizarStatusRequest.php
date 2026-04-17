<?php

namespace App\Http\Requests\Incendio;

use App\Enums\StatusIncendio;
use App\Models\Incendio;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(['ativo', 'em_combate', 'contido', 'resolvido'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Incendio|null $incendio */
            $incendio = $this->route('incendio');

            if (! $incendio instanceof Incendio) {
                return;
            }

            $novoStatus = StatusIncendio::tryFrom((string) $this->input('status'));

            if ($novoStatus === null) {
                return;
            }

            $proximoPermitido = $incendio->status->proximo();

            if ($proximoPermitido === null || $novoStatus !== $proximoPermitido) {
                $validator->errors()->add(
                    'status',
                    "Transição inválida: {$incendio->status->value} não pode ir para {$novoStatus->value}."
                );
            }
        });
    }
}
