<?php

namespace App\Http\Requests;

use App\Models\Entidade;
use Illuminate\Foundation\Http\FormRequest;

class StoreEntidadeRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'is_cliente'     => ['required', 'boolean'],
			'is_fornecedor'  => ['required', 'boolean'],
			'nif'            => [
				'required',
				'string',
				function ($attr, $value, $fail) {
					$hash = hash('sha256', Entidade::normalizeNif($value));
					if (\App\Models\Entidade::where('nif_hash', $hash)->exists()) {
						$fail('O NIF já existe.');
					}
				}
			],
			'nome'           => ['required', 'string', 'max:255'],
			'morada'         => ['nullable', 'string'],
			'codigo_postal'  => ['nullable', 'regex:/^\d{4}-\d{3}$/'],
			'localidade'     => ['nullable', 'string'],
			'pais_id'        => ['nullable', 'exists:paises,id'],
			'telefone'       => ['nullable', 'string', 'max:50'],
			'telemovel'      => ['nullable', 'string', 'max:50'],
			'website'        => ['nullable', 'url'],
			'email'          => ['nullable', 'email'],
			'consentimento_rgpd' => ['required', 'in:sim,nao'],
			'observacoes'    => ['nullable', 'string'],
			'estado'         => ['required', 'in:ativo,inativo'],
		];
	}
}
