<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use App\Models\PropostaLinha;
use App\Models\EncomendaCliente;
use App\Models\Entidade;
use App\Models\Artigo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Services\SequenceService;
use App\Services\DocService;

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
			$p->data_proposta = $p->data_proposta?->format('Y-m-d') ?: null;
			$p->validade = $p->validade?->format('Y-m-d') ?: null;
			return $p;
		});

		return Inertia::render('Propostas/Index', [
			'propostas' => $propostas,
			'filters'   => $request->only('search'),
		]);
	}

	/** --------------------------------------------------------------
	 *  SHOW (para edição)
	 * -------------------------------------------------------------- */
	public function show(Proposta $proposta)
	{
		$proposta->load([
			'cliente:id,nome',
			'linhas.artigo:id,referencia,nome',
			'linhas.fornecedor:id,nome'
		]);

		return response()->json($proposta);
	}

	/** --------------------------------------------------------------
	 *  STORE (criação)
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
			'linhas.*.quantidade'    => ['required', 'numeric', 'gt:0'],
			'linhas.*.preco_unitario' => ['required', 'numeric', 'gte:0'],
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
					'qtd'           => $l['quantidade'],
					'preco'         => $l['preco_unitario'],
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
	 *  UPDATE (edição)
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

				PropostaLinha::create([
					'proposta_id'   => $proposta->id,
					'artigo_id'     => $artigo->id,
					'fornecedor_id' => $l['fornecedor_id'] ?? null,
					'descricao'     => $artigo->nome,
					'qtd'           => $l['quantidade'],
					'preco'         => $l['preco_unitario'],
					'preco_custo'   => $l['preco_custo'] ?? null,
					'total_linha'   => $subtotal,
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
		$proposta->update(['estado' => 'fechado']);
		return back()->with('success', 'Proposta fechada com sucesso.');
	}

	/** --------------------------------------------------------------
	 *  PDF + SALVAR NO ARQUIVO DIGITAL
	 * -------------------------------------------------------------- */
	public function pdf(Proposta $proposta, DocService $docService)
	{
		$proposta->load([
			'cliente:id,nome,morada,codigo_postal,localidade',
			'linhas.artigo:id,referencia',
			'linhas.fornecedor:id,nome'
		]);

		$view = view('pdf.proposta', ['proposta' => $proposta])->render();

		$dompdf = app('dompdf.wrapper');
		$dompdf->loadHTML($view)->setPaper('a4', 'portrait');

		// GERA O PDF UMA VEZ SÓ
		$pdfContent = $dompdf->output();

		// SALVA NO ARQUIVO DIGITAL
		$docService->storeGenerated(
			pdfContent: $pdfContent,
			title: "Proposta #{$proposta->numero}",
			documentableType: Proposta::class,
			documentableId: $proposta->id,
			userId: auth()->id(),
			meta: [
				'tags' => ['proposta', 'cliente'],
				'notes' => "Gerado em " . now()->format('d/m/Y H:i')
			]
		);

		// RETORNA O MESMO PDF (sem re-renderizar!)
		return response($pdfContent, 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="Proposta-' . $proposta->numero . '.pdf"'
		]);
	}

	/** --------------------------------------------------------------
	 *  CONVERTER EM ENCOMENDA
	 * -------------------------------------------------------------- */
	public function converter($id)
	{
		$proposta = Proposta::with(['linhas', 'cliente'])->findOrFail($id);

		// Verificar se a proposta está fechada
		if ($proposta->estado !== 'fechado') {
			return back()->with('error', 'Apenas propostas fechadas podem ser convertidas em encomendas');
		}

		// Calcular total das linhas - USANDO OS CAMPOS CORRETOS
		$total = $proposta->linhas->sum(function ($linha) {
			return ($linha->qtd ?? 0) * ($linha->preco ?? 0);
		});

		// Criar encomenda
		$encomenda = SalesOrder::create([
			'cliente_id' => $proposta->cliente_id,
			'validade' => $proposta->validade,
			'total' => $total,
			'estado' => 'rascunho',
			'data_proposta' => null,
			'proposta_id' => $proposta->id,
		]);

		// Copiar linhas - MAPEAMENTO CORRETO DOS CAMPOS
		foreach ($proposta->linhas as $linha) {
			SalesOrderLine::create([
				'sales_order_id' => $encomenda->id,
				'artigo_id' => $linha->artigo_id,
				'descricao' => $linha->descricao,
				'quantidade' => $linha->qtd, // ← CORRIGIDO: qtd → quantidade
				'preco' => $linha->preco,
				'iva_id' => $linha->iva_id,
				'fornecedor_id' => $linha->fornecedor_id,
				'total' => $linha->total_linha, // ← CORRIGIDO: total_linha → total
			]);
		}

		// Recalcular total baseado nas linhas criadas
		$novoTotal = $encomenda->linhas()->sum('total');
		$encomenda->update(['total' => $novoTotal]);

		// Gerar número
		try {
			$seq = app(SequenceService::class);
			$encomenda->numero = $seq->next('sales_orders_' . now()->year, 'EC', 4);
			$encomenda->save();
		} catch (\Exception $e) {
			$encomenda->numero = 'EC-' . now()->year . '-' . str_pad($encomenda->id, 4, '0', STR_PAD_LEFT);
			$encomenda->save();
		}

		return redirect()->route('encomendas.clientes.index')
			->with('success', 'Proposta convertida para encomenda com sucesso');
	}
}
