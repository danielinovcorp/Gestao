<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFornecedorFaturaRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()->can('create', \App\Models\FornecedorFatura::class);
	}

	public function rules(): array
	{
		return [
			'numero' => ['required', 'string', 'max:50', 'unique:fornecedor_faturas,numero'],
			'data_fatura' => ['required', 'date'],
			'data_vencimento' => ['nullable', 'date', 'after_or_equal:data_fatura'],
			'fornecedor_id' => ['required', 'exists:entidades,id'],
			'encomenda_fornecedor_id' => ['nullable', 'exists:encomendas_fornecedor,id'],
			'valor_total' => ['required', 'numeric', 'min:0'],
			'estado' => ['required', 'in:pendente,paga'],
			'documento' => ['nullable', 'file', 'max:10240'],     // 10MB
			'comprovativo' => ['nullable', 'file', 'max:10240'],  // 10MB
		];
	}
}
