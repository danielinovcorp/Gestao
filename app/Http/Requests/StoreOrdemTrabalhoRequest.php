<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\OrdemTrabalho;

class StoreOrdemTrabalhoRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	} // ajuste se usar policies

	public function rules(): array
	{
		return [
			'cliente_id' => ['required', 'exists:entidades,id'],
			'servico_id' => ['required', 'exists:artigos,id'],
			'descricao' => ['required', 'string', 'min:3'],
			'data_inicio' => ['nullable', 'date'],
			'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
			'estado' => ['required', 'in:' . implode(',', array_keys(OrdemTrabalho::ESTADOS))],
			'prioridade' => ['required', 'in:' . implode(',', array_keys(OrdemTrabalho::PRIORIDADES))],
			'observacoes' => ['nullable', 'string'],
		];
	}
}
