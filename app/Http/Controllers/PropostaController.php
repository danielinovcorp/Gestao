<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use App\Models\PropostaLinha;
use App\Models\Entidade;
use App\Models\Artigo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PropostaController extends Controller
{
	public function index(Request $request)
	{
		$propostas = Proposta::query()
			->with('cliente:id,nome')
			->when($request->estado, fn($q, $v) => $q->where('estado', $v))
			->when($request->cliente_id, fn($q, $v) => $q->where('cliente_id', $v))
			->orderByDesc('id')
			->paginate(15)
			->withQueryString();

		return Inertia::render('Propostas/Index', [
			'propostas' => $propostas,
			'filters' => $request->only('estado', 'cliente_id'),
		]);
	}

	public function create()
	{
		abort(404);
	} // usamos Dialog no front
	public function show(Proposta $proposta)
	{
		abort(404);
	} // idem
	public function edit(Proposta $proposta)
	{
		abort(404);
	} // idem

	public function store(Request $request)
	{
		$payload = $request->validate([
			'cliente_id' => [
				'required',
				Rule::exists('entidades', 'id')->where('tipo_cliente', 1), // garante que é Cliente
			],
			'estado' => ['nullable', 'in:rascunho,fechado'],

			'linhas' => ['required', 'array', 'min:1'],
			'linhas.*.artigo_id' => ['required', 'exists:artigos,id'],
			'linhas.*.fornecedor_id' => [
				'nullable',
				Rule::exists('entidades', 'id')->where('tipo_fornecedor', 1), // se informado, tem de ser Fornecedor
			],
			'linhas.*.quantidade' => ['required', 'numeric', 'gt:0'],
			'linhas.*.preco_unitario' => ['required', 'numeric', 'gte:0'],
			'linhas.*.preco_custo' => ['nullable', 'numeric', 'gte:0'],

			'observacoes' => ['nullable', 'array'],
		]);

		return DB::transaction(function () use ($payload) {
			$p = Proposta::create([
				'numero' => Proposta::nextNumero(),
				'cliente_id' => $payload['cliente_id'],
				'estado' => 'rascunho',
			]);

			foreach ($payload['linhas'] as $l) {
				$artigo = Artigo::find($l['artigo_id']);
				$subtotal = bcmul($l['quantidade'], $l['preco_unitario'], 2);

				PropostaLinha::create([
					'proposta_id' => $p->id,
					'artigo_id' => $artigo->id,
					'fornecedor_id' => $l['fornecedor_id'] ?? null,
					'descricao' => $artigo->nome,
					'referencia' => $artigo->referencia,
					'quantidade' => $l['quantidade'],
					'preco_unitario' => $l['preco_unitario'],
					'preco_custo' => $l['preco_custo'] ?? null,
					'subtotal' => $subtotal,
				]);
			}

			if (!empty($payload['observacoes'])) {
				$p->observacoes = $payload['observacoes'];
			}

			$p->recalcularTotal();

			// Se veio já “fechar”
			if (($payload['estado'] ?? null) === 'fechado') {
				$p->fechar();
			}

			return redirect()->route('propostas.index')->with('ok', 'Proposta criada.');
		});
	}

	public function update(Request $request, Proposta $proposta)
	{
		if ($proposta->estado === 'fechado') {
			return back()->with('err', 'Propostas fechadas não podem ser editadas.');
		}

		$payload = $request->validate([
			'cliente_id' => [
				'required',
				Rule::exists('entidades', 'id')->where('tipo_cliente', 1),
			],

			'linhas' => ['required', 'array', 'min:1'],
			'linhas.*.id' => ['nullable', 'exists:proposta_linhas,id'],
			'linhas.*.artigo_id' => ['required', 'exists:artigos,id'],
			'linhas.*.fornecedor_id' => [
				'nullable',
				Rule::exists('entidades', 'id')->where('tipo_fornecedor', 1),
			],
			'linhas.*.quantidade' => ['required', 'numeric', 'gt:0'],
			'linhas.*.preco_unitario' => ['required', 'numeric', 'gte:0'],
			'linhas.*.preco_custo' => ['nullable', 'numeric', 'gte:0'],

			'observacoes' => ['nullable', 'array'],
		]);

		return DB::transaction(function () use ($payload, $proposta) {
			$proposta->update([
				'cliente_id' => $payload['cliente_id'],
			]);

			// estratégia simples: apaga e recria linhas
			$proposta->linhas()->delete();

			foreach ($payload['linhas'] as $l) {
				$artigo = Artigo::find($l['artigo_id']);
				$subtotal = bcmul($l['quantidade'], $l['preco_unitario'], 2);

				PropostaLinha::create([
					'proposta_id' => $proposta->id,
					'artigo_id' => $artigo->id,
					'fornecedor_id' => $l['fornecedor_id'] ?? null,
					'descricao' => $artigo->nome,
					'referencia' => $artigo->referencia,
					'quantidade' => $l['quantidade'],
					'preco_unitario' => $l['preco_unitario'],
					'preco_custo' => $l['preco_custo'] ?? null,
					'subtotal' => $subtotal,
				]);
			}

			if (!empty($payload['observacoes'])) {
				$proposta->observacoes = $payload['observacoes'];
			}

			$proposta->recalcularTotal();

			return redirect()->route('propostas.index')->with('ok', 'Proposta atualizada.');
		});
	}

	public function destroy(Proposta $proposta)
	{
		if ($proposta->estado === 'fechado') {
			return back()->with('err', 'Propostas fechadas não podem ser removidas.');
		}
		$proposta->delete();
		return back()->with('ok', 'Proposta removida.');
	}

	public function fechar(Proposta $proposta)
	{
		if ($proposta->linhas()->count() === 0) {
			return back()->with('err', 'Não é possível fechar sem linhas.');
		}
		$proposta->fechar();
		return back()->with('ok', 'Proposta fechada.');
	}

	public function pdf(Proposta $proposta)
	{
		$view = view('pdf.proposta', [
			'proposta' => $proposta->load('cliente', 'linhas.artigo', 'linhas.fornecedor')
		])->render();

		$dompdf = app('dompdf.wrapper');
		$dompdf->loadHTML($view)->setPaper('a4');
		return $dompdf->download('Proposta-' . $proposta->numero . '.pdf');
	}

	public function converterEncomenda(Proposta $proposta)
	{
		// Guard: módulo Encomendas ainda não disponível
		if (!class_exists(\App\Models\EncomendaCliente::class) || !class_exists(\App\Models\EncomendaClienteLinha::class)) {
			return back()->with('err', 'Módulo de Encomendas ainda não está disponível.');
		}

		if ($proposta->linhas()->count() === 0) {
			return back()->with('err', 'Sem linhas para converter.');
		}

		$order = \App\Models\EncomendaCliente::create([
			'numero' => \App\Models\EncomendaCliente::nextNumero(),
			'cliente_id' => $proposta->cliente_id,
			'estado' => 'rascunho',
			'origem_tipo' => 'proposta',
			'origem_id' => $proposta->id,
		]);

		foreach ($proposta->linhas as $l) {
			\App\Models\EncomendaClienteLinha::create([
				'encomenda_cliente_id' => $order->id,
				'artigo_id' => $l->artigo_id,
				'descricao' => $l->descricao,
				'referencia' => $l->referencia,
				'quantidade' => $l->quantidade,
				'preco_unitario' => $l->preco_unitario,
				'subtotal' => $l->subtotal,
			]);
		}

		$order->recalcularTotal();

		return redirect()->route('encomendas-clientes.edit', $order->id)
			->with('ok', 'Encomenda (rascunho) criada a partir da Proposta.');
	}
}
