<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdemTrabalhoRequest;
use App\Http\Requests\UpdateOrdemTrabalhoRequest;
use App\Models\OrdemTrabalho;
use App\Models\Entidade;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrdemTrabalhoController extends Controller
{
	public function index(Request $request)
	{
		// filtros simples
		$filters = [
			'search' => $request->string('search')->toString(),
			'estado' => $request->string('estado')->toString(),
		];

		$query = OrdemTrabalho::query()
			->with(['cliente:id,nome', 'responsavel:id,name'])
			->when($filters['search'], function ($q, $s) {
				$q->where(function ($qq) use ($s) {
					$qq->where('numero', 'like', "%{$s}%")
						->orWhere('descricao', 'like', "%{$s}%");
				});
			})
			->when($filters['estado'], fn($q, $e) => $q->where('estado', $e))
			->orderByDesc('id');

		$ordens = $query->paginate(10)->withQueryString();

		// opções para selects (clientes e utilizadores)
		$clientes = Entidade::query()
			->select('id', 'nome')
			->where(function ($q) {
				// se a tua tabela tiver coluna "tipo", filtra por Cliente
				$q->where('tipo', 'cliente')->orWhere('tipo', 'both');
			})
			->orderBy('nome')
			->limit(500)
			->get();

		$responsaveis = User::query()->select('id', 'name')->orderBy('name')->get();

		return Inertia::render('OrdensTrabalho/Index', [
			'ordens'       => $ordens,
			'filters'      => $filters,
			'clientes'     => $clientes,
			'responsaveis' => $responsaveis,
			'estados'      => OrdemTrabalho::ESTADOS,
		]);
	}

	public function store(StoreOrdemTrabalhoRequest $request)
	{
		$data = $request->validated();
		$data['numero'] = OrdemTrabalho::proximoNumero();
		$ot = OrdemTrabalho::create($data);

		return back()->with('success', "Ordem de Trabalho {$ot->numero} criada.");
	}

	public function update(UpdateOrdemTrabalhoRequest $request, OrdemTrabalho $orden)
	{
		$orden->update($request->validated());
		return back()->with('success', "Ordem de Trabalho {$orden->numero} atualizada.");
	}

	public function destroy(OrdemTrabalho $orden)
	{
		$n = $orden->numero;
		$orden->delete();
		return back()->with('success', "Ordem de Trabalho {$n} removida.");
	}
}
