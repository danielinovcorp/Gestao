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
			->when($request->filled('search'), function ($q) use ($request) {
				$q->where(function ($query) use ($request) {
					$query->where('banco', 'like', "%{$request->search}%")
						->orWhere('titular', 'like', "%{$request->search}%")
						->orWhere('iban', 'like', "%{$request->search}%");
				});
			})
			->when($request->filled('ativo'), fn($q) => $q->where('ativo', $request->boolean('ativo')))
			->orderBy('ativo', 'desc')
			->orderBy('banco')
			->orderBy('titular');

		return Inertia::render('Financeiro/ContasBancarias/Index', [
			'filters' => $request->only(['search', 'ativo']),
			'contas' => $query->paginate(20)->through(fn($c) => [
				'id' => $c->id,
				'banco' => $c->banco,
				'titular' => $c->titular,
				'iban' => $this->formatarIban($c->iban),
				'swift_bic' => $c->swift_bic,
				'numero_conta' => $c->numero_conta,
				'saldo_abertura' => number_format($c->saldo_abertura, 2, ',', '.'),
				'ativo' => $c->ativo,
				'notas' => $c->notas,
				'created_at' => $c->created_at?->format('d/m/Y H:i'),
				'updated_at' => $c->updated_at?->format('d/m/Y H:i'),
			]),
		]);
	}

	public function store(StoreContaBancariaRequest $request)
	{
		try {
			ContaBancaria::create($request->validated());
			return back()->with('success', 'Conta bancária criada com sucesso.');
		} catch (\Exception $e) {
			return back()->with('error', 'Erro ao criar conta bancária.');
		}
	}

	public function update(UpdateContaBancariaRequest $request, ContaBancaria $conta_bancaria)
	{
		try {
			$conta_bancaria->update($request->validated());
			return back()->with('success', 'Conta bancária atualizada com sucesso.');
		} catch (\Exception $e) {
			return back()->with('error', 'Erro ao atualizar conta bancária.');
		}
	}

	public function destroy(ContaBancaria $conta_bancaria)
	{
		try {
			$conta_bancaria->delete();
			return back()->with('success', 'Conta bancária removida com sucesso.');
		} catch (\Exception $e) {
			return back()->with('error', 'Erro ao remover conta bancária.');
		}
	}

	private function formatarIban($iban)
	{
		// Formata o IBAN para exibição (grupos de 4 caracteres)
		$iban = preg_replace('/\s+/', '', $iban);
		return trim(chunk_split($iban, 4, ' '));
	}
}
