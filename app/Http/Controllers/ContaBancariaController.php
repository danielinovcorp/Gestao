<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContaBancariaRequest;
use App\Http\Requests\UpdateContaBancariaRequest;
use App\Models\ContaBancaria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContaBancariaController extends Controller
{
	public function index(Request $request)
	{
		$query = ContaBancaria::query()
			->when($request->filled('ativo'), fn($q) => $q->where('ativo', $request->boolean('ativo')))
			->orderBy('banco')->orderBy('titular');

		return Inertia::render('Financeiro/ContasBancarias/Index', [
			'filters' => $request->only(['ativo']),
			'contas' => $query->paginate(20)->through(fn($c) => [
				'id' => $c->id,
				'banco' => $c->banco,
				'titular' => $c->titular,
				'iban' => $c->iban,
				'swift_bic' => $c->swift_bic,
				'saldo_abertura' => number_format($c->saldo_abertura, 2, ',', '.'),
				'ativo' => $c->ativo,
			]),
		]);
	}

	public function store(StoreContaBancariaRequest $request)
	{
		ContaBancaria::create($request->validated());
		return back()->with('success', 'Conta criada.');
	}

	public function update(UpdateContaBancariaRequest $request, ContaBancaria $conta_bancaria)
	{
		$conta_bancaria->update($request->validated());
		return back()->with('success', 'Conta atualizada.');
	}

	public function destroy(ContaBancaria $conta_bancaria)
	{
		$conta_bancaria->delete();
		return back()->with('success', 'Conta removida.');
	}
}
