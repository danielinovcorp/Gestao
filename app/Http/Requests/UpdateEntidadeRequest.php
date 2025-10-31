<?php

namespace App\Http\Requests;

use App\Models\Entidade;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEntidadeRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$id = $this->route('entidade'); // /entidades/{entidade}
		return [
			'is_cliente'     => ['sometimes', 'boolean'],
			'is_fornecedor'  => ['sometimes', 'boolean'],
			'nif'            => [
				'sometimes',
				'string',
				function ($attr, $value, $fail) use ($id) {
					if (!filled($value)) return;
					$hash = hash('sha256', Entidade::normalizeNif($value));
					$exists = \App\Models\Entidade::where('nif_hash', $hash)->where('id', '!=', $id)->exists();
					if ($exists) $fail('O NIF já existe.');
				}
			],
			'nome'           => ['sometimes', 'string', 'max:255'],
			'morada'         => ['sometimes', 'string', 'nullable'],
			'codigo_postal'  => ['sometimes', 'regex:/^\d{4}-\d{3}$/', 'nullable'],
			'localidade'     => ['sometimes', 'string', 'nullable'],
			'pais_id'        => ['sometimes', 'exists:paises,id', 'nullable'],
			'telefone'       => ['sometimes', 'string', 'max:50', 'nullable'],
			'telemovel'      => ['sometimes', 'string', 'max:50', 'nullable'],
			'website'        => ['sometimes', 'url', 'nullable'],
			'email'          => ['sometimes', 'email', 'nullable'],
			'consentimento_rgpd' => ['sometimes', 'in:sim,nao'],
			'observacoes'    => ['sometimes', 'string', 'nullable'],
			'estado'         => ['sometimes', 'in:ativo,inativo'],
		];
	}
}
