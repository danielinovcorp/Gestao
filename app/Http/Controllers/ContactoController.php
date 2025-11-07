<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

class ContactoController extends Controller
{
	public function index(Request $r)
	{
		$q = Contacto::query()
			->with(['entidade:id,nome', 'funcao:id,nome'])
			->when($r->entidade_id, fn($qq) => $qq->where('entidade_id', $r->entidade_id))
			->when($r->q, function ($qq) use ($r) {
				$s = trim((string) $r->q);
				$qq->where(function ($w) use ($s) {
					$w->where('nome', 'like', "%{$s}%")
						->orWhere('apelido', 'like', "%{$s}%")
						->when(
							Schema::hasColumn('contactos', 'telemovel'),
							fn($x) =>
							$x->orWhere('telemovel', 'like', "%{$s}%")
						);
				});
			});

		$data = $q->latest('id')->paginate(min(100, (int) $r->query('per_page', 15)));

		$data->getCollection()->transform(function (Contacto $c) {
			return [
				'id'         => $c->id,
				'numero'     => $c->numero,

				'entidade_id' => $c->entidade_id,
				'funcao_id'  => $c->funcao_id,

				'entidade'   => $c->entidade?->nome,
				'funcao'     => $c->funcao?->nome,

				'nome'       => $c->nome,
				'apelido'    => $c->apelido,

				'telefone'   => $c->telefone,                 // accessor (decrypt telefone_enc)
				'telemovel'  => $c->telemovel ?? $c->telemovel_dec,
				'email'      => $c->email,                    // accessor (decrypt email_enc)

				'consentimento_rgpd' => $c->consentimento_rgpd,
				'observacoes' => $c->observacoes,
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
			'observacoes'        => ['nullable', 'string'],
			'consentimento_rgpd' => ['required', 'in:sim,nao'],
			'estado'             => ['required', 'in:ativo,inativo'],
		]);

		$columns = Schema::getColumnListing('contactos');

		// base segura
		$payload = Arr::only($data, [
			'entidade_id',
			'nome',
			'apelido',
			'funcao_id',
			'observacoes',
			'consentimento_rgpd',
			'estado',
		]);

		// cifrar mapeando para *_enc se existirem
		if (!empty($data['telefone']) && in_array('telefone_enc', $columns)) {
			$payload['telefone_enc'] = encrypt($data['telefone']);
		}
		if (array_key_exists('telemovel', $data)) {
			if (in_array('telemovel_enc', $columns)) {
				$payload['telemovel_enc'] = $data['telemovel'] ? encrypt($data['telemovel']) : null;
			}
			if (in_array('telemovel', $columns)) {
				$payload['telemovel'] = $data['telemovel'] ?: null;
			}
		}
		if (!empty($data['email']) && in_array('email_enc', $columns)) {
			$payload['email_enc'] = encrypt($data['email']);
		}

		// 🔢 gerar numero de forma robusta (lock + fallback) e com retry
		if (in_array('numero', $columns)) {
			$payload['numero'] = $this->nextNumeroContactos();
		}

		try {
			$c = Contacto::create($payload);
		} catch (QueryException $e) {
			// Se colidir por UNIQUE(numero), tenta mais uma vez com um novo número
			if (str_contains(strtolower($e->getMessage()), 'duplicate') && in_array('numero', $columns)) {
				Log::warning('Colisão de numero em contactos, tentando novo numero...', ['msg' => $e->getMessage()]);
				$payload['numero'] = $this->nextNumeroContactos(forceMaxFallback: true);
				$c = Contacto::create($payload);
			} else {
				throw $e;
			}
		}

		activity()->performedOn($c)->withProperties(['id' => $c->id])->log('criou contacto');

		return response()->json($c->loadMissing('entidade:id,nome', 'funcao:id,nome'), 201);
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
			'observacoes'        => ['sometimes', 'nullable', 'string'],
			'consentimento_rgpd' => ['sometimes', 'required', 'in:sim,nao'],
			'estado'             => ['sometimes', 'required', 'in:ativo,inativo'],
		]);

		$columns = Schema::getColumnListing('contactos');

		$payload = Arr::only($data, [
			'entidade_id',
			'nome',
			'apelido',
			'funcao_id',
			'observacoes',
			'consentimento_rgpd',
			'estado',
		]);

		if (array_key_exists('telefone', $data) && in_array('telefone_enc', $columns)) {
			$payload['telefone_enc'] = $data['telefone'] ? encrypt($data['telefone']) : null;
		}
		if (array_key_exists('telemovel', $data)) {
			if (in_array('telemovel_enc', $columns)) {
				$payload['telemovel_enc'] = $data['telemovel'] ? encrypt($data['telemovel']) : null;
			}
			if (in_array('telemovel', $columns)) {
				$payload['telemovel'] = $data['telemovel'] ?: null;
			}
		}
		if (array_key_exists('email', $data) && in_array('email_enc', $columns)) {
			$payload['email_enc'] = $data['email'] ? encrypt($data['email']) : null;
		}

		unset($payload['numero']); // nunca alterar numero

		$contacto->update($payload);
		activity()->performedOn($contacto)->withProperties(['id' => $contacto->id])->log('editou contacto');

		return $contacto->loadMissing('entidade:id,nome', 'funcao:id,nome');
	}

	public function destroy(Contacto $contacto)
	{
		$contacto->delete();
		activity()->performedOn($contacto)->log('removeu contacto');
		return response()->noContent();
	}

	/**
	 * Gera o próximo numero para contactos com lock pessimista.
	 * Se o teu SequenceService estiver OK, usamos ele; caso contrário, usamos fallback por MAX(numero)+1.
	 */
	protected function nextNumeroContactos(bool $forceMaxFallback = false): int
	{
		// 1) tenta SequenceService (se existir e não for forçado o fallback)
		if (!$forceMaxFallback && class_exists(\App\Services\SequenceService::class)) {
			try {
				// ideal: o teu service já deve tratar lock/atomicidade
				return SequenceService::next('contactos');
			} catch (\Throwable $e) {
				Log::warning('SequenceService falhou, usando fallback MAX+1', ['err' => $e->getMessage()]);
				// cai para fallback
			}
		}

		// 2) fallback robusto: lock tabela/linha e calcula MAX(numero)+1
		return DB::transaction(function () {
			// lock “barato” — força serialização da leitura até o commit
			// Em MySQL InnoDB, um select MAX não bloqueia linhas; mas o uso em transação + insert subsequente reduz janela de corrida.
			$max = (int) (DB::table('contactos')->lockForUpdate()->max('numero') ?? 0);
			$next = $max + 1;
			// não grava em sequences; grava direto no contacto ao criar
			return $next;
		}, 3); // 3 tentativas em caso de deadlock
	}
}
