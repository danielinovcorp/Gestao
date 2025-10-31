<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContaBancariaRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('criar contas bancarias') ?? true;
	}

	public function rules(): array
	{
		return [
			'banco' => ['nullable', 'string', 'max:120'],
			'titular' => ['nullable', 'string', 'max:120'],
			'iban' => ['required', 'string', 'max:64', 'unique:conta_bancarias,iban'],
			'swift_bic' => ['nullable', 'string', 'max:20'],
			'numero_conta' => ['nullable', 'string', 'max:64'],
			'saldo_abertura' => ['required', 'numeric', 'min:0'],
			'ativo' => ['boolean'],
			'notas' => ['nullable', 'string'],
		];
	}
}
