<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContaBancariaRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('editar contas bancarias') ?? true;
	}

	public function rules(): array
	{
		$id = $this->route('conta_bancaria')?->id ?? $this->route('id');
		return [
			'banco' => ['nullable', 'string', 'max:120'],
			'titular' => ['nullable', 'string', 'max:120'],
			'iban' => ['required', 'string', 'max:64', Rule::unique('conta_bancarias', 'iban')->ignore($id)],
			'swift_bic' => ['nullable', 'string', 'max:20'],
			'numero_conta' => ['nullable', 'string', 'max:64'],
			'saldo_abertura' => ['required', 'numeric', 'min:0'],
			'ativo' => ['boolean'],
			'notas' => ['nullable', 'string'],
		];
	}
}
