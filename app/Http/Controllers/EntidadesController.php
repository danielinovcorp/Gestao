<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Entidade;

class EntidadesController extends Controller
{
	public function index(Request $request)
	{
		// Rota decide o tipo (clientes|fornecedores)
		$tipo = $request->routeIs('entidades.index.clientes') ? 'cliente'
			: ($request->routeIs('entidades.index.fornecedores') ? 'fornecedor' : null);

		$q = Entidade::query();

		if ($tipo === 'cliente')     $q->clientes();
		if ($tipo === 'fornecedor')  $q->fornecedores();

		// filtros de pesquisa (opcional, mesmo param 'search' da API)
		$q->search($request->query('search'));

		$perPage = min(100, (int) $request->query('per_page', 15));
		$data = $q->orderBy('id', 'desc')->paginate($perPage);

		// Transform para lista (evitando campos sensíveis)
		$items = $data->through(function ($e) {
			return [
				'id'           => $e->id,
				'numero'       => $e->numero,
				'nome'         => $e->nome,
				'is_cliente'   => $e->is_cliente,
				'is_fornecedor' => $e->is_fornecedor,
				'localidade'   => $e->localidade,
				'pais_id'      => $e->pais_id,
				'estado'       => $e->estado,
				'created_at'   => $e->created_at,
			];
		});

		return Inertia::render('Entidades/Index', [
			'tipo'       => $tipo,
			'search'     => (string) $request->query('search', ''),
			'per_page'   => $perPage,
			'entidades'  => $items, // paginator com data transformada
		]);
	}
}
