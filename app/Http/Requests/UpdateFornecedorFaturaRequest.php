<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFornecedorFaturaRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()->can('update', $this->route('fornecedor_fatura'));
	}

	public function rules(): array
	{
		$fatura = $this->route('fornecedor_fatura');

		return [
			'numero' => [
				'required',
				'string',
				'max:50',
				Rule::unique('fornecedor_faturas', 'numero')->ignore($fatura->id)
			],
			'data_fatura' => ['required', 'date'],
			'data_vencimento' => ['nullable', 'date', 'after_or_equal:data_fatura'],
			'fornecedor_id' => ['required', 'exists:entidades,id'],
			'encomenda_fornecedor_id' => ['nullable', 'exists:encomendas_fornecedor,id'],
			'valor_total' => ['required', 'numeric', 'min:0'],
			'estado' => ['required', 'in:pendente,paga'],
			'documento' => ['nullable', 'file', 'max:10240'],
			'comprovativo' => ['nullable', 'file', 'max:10240'],
		];
	}
}
