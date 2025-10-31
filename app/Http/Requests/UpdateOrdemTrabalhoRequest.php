<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\OrdemTrabalho;

class UpdateOrdemTrabalhoRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}
	public function rules(): array
	{
		return [
			'cliente_id'     => ['required', 'exists:entidades,id'],
			'responsavel_id' => ['required', 'exists:users,id'],
			'descricao'      => ['required', 'string', 'min:3'],
			'data_inicio'    => ['nullable', 'date'],
			'data_fim'       => ['nullable', 'date', 'after_or_equal:data_inicio'],
			'estado'         => ['required', 'in:' . implode(',', OrdemTrabalho::ESTADOS)],
			'observacoes'    => ['nullable', 'string'],
		];
	}
}
