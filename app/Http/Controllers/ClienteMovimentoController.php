<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteMovimentoRequest;
use App\Models\ClienteMovimento;
use App\Models\Entidade;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteMovimentoController extends Controller
{
	public function index(Request $request)
	{
		$clienteId = $request->integer('cliente');

		$movs = ClienteMovimento::query()
			->with('cliente:id,nome')
			->when($clienteId, fn($q) => $q->where('cliente_id', $clienteId))
			->orderByDesc('data')->orderByDesc('id')
			->paginate(20);

		// saldo corrente (sum crédito - débito) do filtro
		$saldo = ClienteMovimento::query()
			->when($clienteId, fn($q) => $q->where('cliente_id', $clienteId))
			->selectRaw('COALESCE(SUM(credito - debito),0) as saldo')
			->value('saldo');

		return Inertia::render('Financeiro/ContaCorrenteClientes/Index', [
			'filters' => $request->only(['cliente']),
			'movimentos' => $movs->through(fn($m) => [
				'id' => $m->id,
				'data' => $m->data->format('Y-m-d'),
				'cliente' => $m->cliente?->nome,
				'descricao' => $m->descricao,
				'doc' => trim(($m->documento_tipo ?? '') . ' ' . ($m->documento_numero ?? '')),
				'debito' => number_format($m->debito, 2, ',', '.'),
				'credito' => number_format($m->credito, 2, ',', '.'),
			]),
			'clientes' => Entidade::where(fn($q) => $q->where('tipo', 'cliente')->orWhere('tipo', 'both'))
				->orderBy('nome')->get(['id', 'nome']),
			'saldo' => number_format($saldo, 2, ',', '.'),
		]);
	}

	public function store(StoreClienteMovimentoRequest $request)
	{
		ClienteMovimento::create($request->validated());
		return back()->with('success', 'Lançamento inserido.');
	}
}
