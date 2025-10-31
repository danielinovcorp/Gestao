<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Services\SequenceService;
use Illuminate\Http\Request;

class ContactoController extends Controller
{
	public function index(Request $r)
	{
		$q = Contacto::query()
			->with([
				'entidade:id,nome',
				'funcao:id,nome',
			])
			->when($r->entidade_id, fn($qq) => $qq->where('entidade_id', $r->entidade_id))
			->when($r->q, function ($qq) use ($r) {
				$s = trim((string) $r->q);
				$qq->where(function ($w) use ($s) {
					$w->where('nome', 'like', "%{$s}%")
						->orWhere('apelido', 'like', "%{$s}%")
						->orWhere('email', 'like', "%{$s}%")
						->orWhere('telefone', 'like', "%{$s}%")
						->orWhere('telemovel', 'like', "%{$s}%");
				});
			});

		$data = $q->latest('id')->paginate(min(100, (int) $r->query('per_page', 15)));

		// Formata saída p/ DataTable (Nome, Apelido, Função, Entidade, Telefone, Telemóvel, Email)
		$data->getCollection()->transform(function (Contacto $c) {
			return [
				'id'         => $c->id,
				'numero'     => $c->numero,
				'nome'       => $c->nome,
				'apelido'    => $c->apelido,
				'funcao'     => $c->funcao?->nome,
				'entidade'   => $c->entidade?->nome,
				'telefone'   => $c->telefone,
				'telemovel'  => $c->telemovel,
				'email'      => $c->email,
				'estado'     => $c->estado,
			];
		});

		return $data;
	}

	public function store(Request $r)
	{
		$data = $r->validate([
			'entidade_id'        => ['required', 'integer', 'exists:entidades,id'],
			'nome'               => ['required', 'string', 'max:255'],
			'apelido'            => ['nullable', 'string', 'max:255'],
			'funcao_id'          => ['nullable', 'integer', 'exists:funcoes_contacto,id'],
			'telefone'           => ['nullable', 'string', 'max:40'],
			'telemovel'          => ['nullable', 'string', 'max:40'],
			'email'              => ['nullable', 'email', 'max:255'],
			'cargo'              => ['nullable', 'string', 'max:120'],
			'observacoes'        => ['nullable', 'string'],
			'consentimento_rgpd' => ['required', 'in:sim,nao'],
			'estado'             => ['required', 'in:ativo,inativo'],
			'principal'          => ['sometimes', 'boolean'],
		]);

		// Número incremental (usa o teu SequenceService)
		$data['numero'] = $data['numero'] ?? SequenceService::next('contactos');

		$c = Contacto::create($data);
		activity()->performedOn($c)->withProperties($data)->log('criou contacto');

		return response()->json($c, 201);
	}

	public function show(Contacto $contacto)
	{
		return $contacto->loadMissing('entidade:id,nome', 'funcao:id,nome');
	}

	public function update(Request $r, Contacto $contacto)
	{
		$data = $r->validate([
			'entidade_id'        => ['sometimes', 'integer', 'exists:entidades,id'],
			'nome'               => ['sometimes', 'string', 'max:255'],
			'apelido'            => ['sometimes', 'nullable', 'string', 'max:255'],
			'funcao_id'          => ['sometimes', 'nullable', 'integer', 'exists:funcoes_contacto,id'],
			'telefone'           => ['sometimes', 'nullable', 'string', 'max:40'],
			'telemovel'          => ['sometimes', 'nullable', 'string', 'max:40'],
			'email'              => ['sometimes', 'nullable', 'email', 'max:255'],
			'cargo'              => ['sometimes', 'nullable', 'string', 'max:120'],
			'observacoes'        => ['sometimes', 'nullable', 'string'],
			'consentimento_rgpd' => ['sometimes', 'required', 'in:sim,nao'],
			'estado'             => ['sometimes', 'required', 'in:ativo,inativo'],
			'principal'          => ['sometimes', 'boolean'],
		]);

		// Se quiser impedir mudança de numero por update, remova da próxima linha se vier
		unset($data['numero']);

		$contacto->update($data);
		activity()->performedOn($contacto)->withProperties($data)->log('editou contacto');

		return $contacto->loadMissing('entidade:id,nome', 'funcao:id,nome');
	}

	public function destroy(Contacto $contacto)
	{
		$contacto->delete();
		activity()->performedOn($contacto)->log('removeu contacto');
		return response()->noContent();
	}
}
