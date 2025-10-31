<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEntidadeRequest;
use App\Http\Requests\UpdateEntidadeRequest;
use App\Models\Entidade;
use App\Services\SequenceService;
use Illuminate\Http\Request;

class EntidadeController extends Controller
{
	public function index(Request $request)
	{
		$q = Entidade::query();

		// Filtro por tipo
		if ($t = $request->query('tipo')) {
			if ($t === 'cliente') {
				$q->clientes();
			}
			if ($t === 'fornecedor') {
				$q->fornecedores();
			}
		}

		// Busca simples (nome / NIF - via hash)
		if ($s = $request->query('search')) {
			$s = trim($s);
			$q->where(function ($qq) use ($s) {
				$qq->where('nome', 'like', "%{$s}%")
					->orWhere('nif_hash', hash('sha256', Entidade::normalizeNif($s)));
			});
		}

		// Paginação
		$perPage = min(100, (int) $request->query('per_page', 15));
		$data = $q->orderBy('id', 'desc')->paginate($perPage);

		// Permissão para ver dados sensíveis em claro (Spatie Permission)
		// Crie no seeder: Permission::firstOrCreate(['name' => 'ver entidades sensíveis'])
		$authorized = auth()->user()?->can('ver entidades sensíveis') ?? false;

		// Helpers de máscara (RGPD)
		$maskPhone = function (?string $p) {
			if (!$p) return null;
			$p = preg_replace('/\s+/', '', $p);
			$len = strlen($p);
			if ($len <= 4) return str_repeat('*', $len);
			return substr($p, 0, max(0, $len - 4)) . str_repeat('*', 4);
		};
		$maskEmail = function (?string $e) {
			if (!$e || !str_contains($e, '@')) return null;
			[$u, $d] = explode('@', $e, 2);
			if (strlen($u) <= 2)      $uMask = str_repeat('*', strlen($u));
			else                      $uMask = substr($u, 0, 1) . str_repeat('*', max(0, strlen($u) - 2)) . substr($u, -1);
			return $uMask . '@' . $d;
		};

		// Mapeia campos cifrados para o front (sem devolver NIF em claro)
		$data->getCollection()->transform(function ($e) use ($authorized, $maskPhone, $maskEmail) {
			return [
				'id'                 => $e->id,
				'numero'             => $e->numero,
				'is_cliente'         => $e->is_cliente,
				'is_fornecedor'      => $e->is_fornecedor,
				'nif'                => null, // nunca em claro
				'nome'               => $e->nome,
				'morada'             => $e->morada,
				'codigo_postal'      => $e->codigo_postal,
				'localidade'         => $e->localidade,
				'pais_id'            => $e->pais_id,
				'telefone'           => $authorized ? $e->telefone_enc  : $maskPhone($e->telefone_enc),
				'telemovel'          => $authorized ? $e->telemovel_enc : $maskPhone($e->telemovel_enc),
				'website'            => $e->website,
				'email'              => $authorized ? $e->email_enc     : $maskEmail($e->email_enc),
				'consentimento_rgpd' => $e->consentimento_rgpd,
				'estado'             => $e->estado,
				'created_at'         => $e->created_at,
			];
		});

		return response()->json($data);
	}

	public function store(StoreEntidadeRequest $request)
	{
		$nifNorm = Entidade::normalizeNif((string) $request->input('nif'));
		$numero  = SequenceService::next('entidades');

		$e = new Entidade();
		$e->fill([
			'numero'              => $numero,
			'is_cliente'          => (bool) $request->boolean('is_cliente'),
			'is_fornecedor'       => (bool) $request->boolean('is_fornecedor'),
			'nif_enc'             => $nifNorm, // cifrará via cast
			'nif_hash'            => hash('sha256', $nifNorm),
			'nome'                => (string) $request->input('nome'),
			'morada'              => $request->input('morada'),
			'codigo_postal'       => $request->input('codigo_postal'),
			'localidade'          => $request->input('localidade'),
			'pais_id'             => $request->input('pais_id'),
			'telefone_enc'        => $request->input('telefone'),
			'telemovel_enc'       => $request->input('telemovel'),
			'website'             => $request->input('website'),
			'email_enc'           => $request->input('email'),
			'consentimento_rgpd'  => $request->input('consentimento_rgpd', 'nao'),
			'observacoes'         => $request->input('observacoes'),
			'estado'              => $request->input('estado', 'ativo'),
		]);

		// Garantia: pelo menos um tipo (também validado no FormRequest)
		if (!$e->is_cliente && !$e->is_fornecedor) {
			return response()->json(['message' => 'Selecione Cliente e/ou Fornecedor.'], 422);
		}

		// NIF único (por hash)
		if ($nifNorm) {
			$exists = Entidade::where('nif_hash', hash('sha256', $nifNorm))->exists();
			if ($exists) {
				return response()->json(['message' => 'Este NIF já existe.'], 422);
			}
		}

		$e->save();

		return response()->json(['id' => $e->id, 'numero' => $e->numero], 201);
	}

	public function show(Entidade $entidade)
	{
		return response()->json($entidade);
	}

	public function update(UpdateEntidadeRequest $request, Entidade $entidade)
	{
		// Só atualiza NIF se vier preenchido (evita sobrescrever com vazio)
		if ($request->filled('nif')) {
			$nifNorm = Entidade::normalizeNif((string) $request->input('nif'));
			$entidade->nif_enc  = $nifNorm;
			$entidade->nif_hash = hash('sha256', $nifNorm);
		}

		foreach (
			[
				'is_cliente',
				'is_fornecedor',
				'nome',
				'morada',
				'codigo_postal',
				'localidade',
				'pais_id',
				'website',
				'consentimento_rgpd',
				'observacoes',
				'estado',
			] as $f
		) {
			if ($request->has($f)) {
				$entidade->$f = $request->input($f);
			}
		}

		// Campos cifrados
		foreach (['telefone' => 'telefone_enc', 'telemovel' => 'telemovel_enc', 'email' => 'email_enc'] as $in => $col) {
			if ($request->has($in)) {
				$entidade->$col = $request->input($in);
			}
		}

		// Garantia: pelo menos um tipo
		if (!$entidade->is_cliente && !$entidade->is_fornecedor) {
			return response()->json(['message' => 'Selecione Cliente e/ou Fornecedor.'], 422);
		}

		$entidade->save();

		return response()->json(['ok' => true]);
	}

	public function destroy(Entidade $entidade)
	{
		$entidade->delete();
		return response()->json(['ok' => true]);
	}
}
