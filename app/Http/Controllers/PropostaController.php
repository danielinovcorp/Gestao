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
	/** --------------------------------------------------------------
	 *  INDEX
	 * -------------------------------------------------------------- */
	public function index(Request $request)
	{
		$query = Proposta::query()
			->with(['cliente:id,nome'])
			->select([
				'id',
				'numero',
				'cliente_id',
				'data_proposta',
				'validade',
				'total',
				'estado'
			])
			->when($request->search, function ($q, $search) {
				$q->where('numero', 'like', "%$search%")
					->orWhereHas('cliente', fn($q) => $q->where('nome', 'like', "%$search%"));
			})
			->orderByDesc('id');

		$propostas = $query->paginate(15)->withQueryString();

		// GARANTA QUE total É FLOAT
		$propostas->getCollection()->transform(function ($p) {
			$p->total = (float) $p->total;
			$p->data_proposta = $p->data_proposta?->format('Y-m-d') ?: null; // ADICIONE
			$p->validade = $p->validade?->format('Y-m-d') ?: null; // ADICIONE
			return $p;
		});

		return Inertia::render('Propostas/Index', [
			'propostas' => $propostas,
			'filters'   => $request->only('search'),
		]);
	}

	/** --------------------------------------------------------------
	 *  STORE (criação) - COM NOMES CORRETOS DAS COLUNAS
	 * -------------------------------------------------------------- */
	public function store(Request $request)
	{
		$payload = $request->validate([
			'cliente_id' => ['required', Rule::exists('entidades', 'id')->where('is_cliente', 1)],
			'data_proposta' => ['required', 'date'],
			'validade' => ['required', 'date'],
			'estado' => ['required', 'in:rascunho,fechado'],

			'linhas' => ['required', 'array', 'min:1'],
			'linhas.*.artigo_id'     => ['required', 'exists:artigos,id'],
			'linhas.*.fornecedor_id' => ['nullable', Rule::exists('entidades', 'id')->where('is_fornecedor', 1)],
			'linhas.*.quantidade'    => ['required', 'numeric', 'gt:0'],        // ← CORRETO
			'linhas.*.preco_unitario' => ['required', 'numeric', 'gte:0'],     // ← CORRETO
			'linhas.*.preco_custo'   => ['nullable', 'numeric', 'gte:0'],
		]);

		return DB::transaction(function () use ($payload) {
			// Cria a proposta
			$p = Proposta::create([
				'numero'        => Proposta::nextNumero(),
				'cliente_id'    => $payload['cliente_id'],
				'data_proposta' => $payload['data_proposta'],
				'validade'      => $payload['validade'],
				'estado'        => $payload['estado'],
			]);

			// Processa as linhas e calcula o total
			$total = 0;
			foreach ($payload['linhas'] as $l) {
				$artigo = Artigo::findOrFail($l['artigo_id']);
				$subtotal = $l['quantidade'] * $l['preco_unitario'];
				$total += $subtotal;

				PropostaLinha::create([
					'proposta_id'   => $p->id,
					'artigo_id'     => $artigo->id,
					'fornecedor_id' => $l['fornecedor_id'] ?? null,
					'descricao'     => $artigo->nome,
					'qtd'           => $l['quantidade'],        // ← CORRETO
					'preco'         => $l['preco_unitario'],    // ← CORRETO
					'preco_custo'   => $l['preco_custo'] ?? null,
					'total_linha'   => $subtotal,
				]);
			}

			// Atualiza o total da proposta
			$p->update(['total' => $total]);

			return redirect()->route('propostas.index')
				->with('success', $p->estado === 'fechado'
					? 'Proposta criada e fechada com sucesso!'
					: 'Proposta criada como rascunho!');
		});
	}

	/** --------------------------------------------------------------
	 *  UPDATE (edição) - COM NOMES CORRETOS DAS COLUNAS
	 * -------------------------------------------------------------- */
	public function update(Request $request, Proposta $proposta)
	{
		if ($proposta->estado === 'fechado') {
			return back()->with('error', 'Propostas fechadas não podem ser editadas.');
		}

		$payload = $request->validate([
			'cliente_id' => [
				'required',
				Rule::exists('entidades', 'id')->where('is_cliente', 1),
			],
			'data_proposta' => ['required', 'date'],
			'validade' => ['required', 'date'],
			'estado' => ['required', 'in:rascunho,fechado'],

			'linhas' => ['required', 'array', 'min:1'],
			'linhas.*.artigo_id'     => ['required', 'exists:artigos,id'],
			'linhas.*.fornecedor_id' => [
				'nullable',
				Rule::exists('entidades', 'id')->where('is_fornecedor', 1),
			],
			'linhas.*.quantidade'    => ['required', 'numeric', 'gt:0'],
			'linhas.*.preco_unitario' => ['required', 'numeric', 'gte:0'],
			'linhas.*.preco_custo'   => ['nullable', 'numeric', 'gte:0'],
		]);

		return DB::transaction(function () use ($payload, $proposta) {
			// Atualiza dados básicos da proposta
			$proposta->update([
				'cliente_id'    => $payload['cliente_id'],
				'data_proposta' => $payload['data_proposta'],
				'validade'      => $payload['validade'],
				'estado'        => $payload['estado'],
			]);

			// Remove linhas antigas e cria novas
			$proposta->linhas()->delete();

			// Processa as linhas e calcula o total
			$total = 0;
			foreach ($payload['linhas'] as $l) {
				$artigo = Artigo::findOrFail($l['artigo_id']);
				$subtotal = $l['quantidade'] * $l['preco_unitario'];
				$total += $subtotal;

				// ✅ CORREÇÃO: Usa os nomes corretos das colunas
				PropostaLinha::create([
					'proposta_id'   => $proposta->id,
					'artigo_id'     => $artigo->id,
					'fornecedor_id' => $l['fornecedor_id'] ?? null,
					'descricao'     => $artigo->nome, // ✅ CORRETO
					'qtd'           => $l['quantidade'], // ✅ CORRETO: 'qtd' em vez de 'quantidade'
					'preco'         => $l['preco_unitario'], // ✅ CORRETO: 'preco' em vez de 'preco_unitario'
					'preco_custo'   => $l['preco_custo'] ?? null, // ✅ CORRETO
					'total_linha'   => $subtotal, // ✅ CORRETO: 'total_linha' em vez de 'subtotal'
				]);
			}

			// Atualiza o total da proposta
			$proposta->update(['total' => $total]);

			return redirect()->route('propostas.index')
				->with('success', $proposta->estado === 'fechado'
					? 'Proposta atualizada e fechada com sucesso!'
					: 'Proposta atualizada com sucesso!');
		});
	}

	/** --------------------------------------------------------------
	 *  DESTROY
	 * -------------------------------------------------------------- */
	public function destroy(Proposta $proposta)
	{
		if ($proposta->estado === 'fechado') {
			return back()->with('error', 'Propostas fechadas não podem ser removidas.');
		}
		$proposta->delete();
		return back()->with('success', 'Proposta removida com sucesso.');
	}

	/** --------------------------------------------------------------
	 *  FECHAR (ação separada – botão na listagem)
	 * -------------------------------------------------------------- */
	public function fechar(Proposta $proposta)
	{
		if ($proposta->linhas()->count() === 0) {
			return back()->with('error', 'Não é possível fechar sem linhas.');
		}
		$proposta->fechar();
		return back()->with('success', 'Proposta fechada com sucesso.');
	}

	/** --------------------------------------------------------------
	 *  PDF
	 * -------------------------------------------------------------- */
	public function pdf(Proposta $proposta)
	{
		$proposta->load([
			'cliente:id,nome',
			'linhas.artigo:id,referencia',
		]);

		$view = view('pdf.proposta', [
			'proposta' => $proposta
		])->render();

		$dompdf = app('dompdf.wrapper');
		$dompdf->loadHTML($view)->setPaper('a4', 'portrait');
		return $dompdf->download('Proposta-' . $proposta->numero . '.pdf');
	}

	/** --------------------------------------------------------------
	 *  CONVERTER EM ENCOMENDA
	 * -------------------------------------------------------------- */
	public function converterEncomenda(Proposta $proposta)
	{
		if (
			!class_exists(\App\Models\EncomendaCliente::class) ||
			!class_exists(\App\Models\EncomendaClienteLinha::class)
		) {
			return back()->with('error', 'Módulo de Encomendas ainda não está disponível.');
		}

		if ($proposta->linhas()->count() === 0) {
			return back()->with('error', 'Sem linhas para converter.');
		}

		$order = \App\Models\EncomendaCliente::create([
			'numero'       => \App\Models\EncomendaCliente::nextNumero(),
			'cliente_id'   => $proposta->cliente_id,
			'estado'       => 'rascunho',
			'origem_tipo'  => 'proposta',
			'origem_id'    => $proposta->id,
		]);

		foreach ($proposta->linhas as $l) {
			\App\Models\EncomendaClienteLinha::create([
				'encomenda_cliente_id' => $order->id,
				'artigo_id'            => $l->artigo_id,
				'descricao'            => $l->descricao,
				'referencia'           => $l->artigo->referencia ?? '',
				'quantidade'           => $l->qtd, // ✅ CORRETO: 'qtd'
				'preco_unitario'       => $l->preco, // ✅ CORRETO: 'preco'
				'subtotal'             => $l->total_linha, // ✅ CORRETO: 'total_linha'
			]);
		}

		$order->recalcularTotal();

		return redirect()->route('encomendas.clientes.edit', $order->id)
			->with('success', 'Encomenda (rascunho) criada a partir da Proposta.');
	}
}
