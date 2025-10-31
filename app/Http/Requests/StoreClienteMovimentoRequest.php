<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteMovimentoRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'cliente_id' => ['required', 'exists:entidades,id'],
			'data' => ['required', 'date'],
			'descricao' => ['nullable', 'string', 'max:255'],
			'documento_tipo' => ['nullable', 'in:fatura,recibo,nota_credito,ajuste'],
			'documento_numero' => ['nullable', 'string', 'max:50'],
			'debito' => ['required_without:credito', 'numeric', 'min:0'],
			'credito' => ['required_without:debito', 'numeric', 'min:0'],
		];
	}
}
