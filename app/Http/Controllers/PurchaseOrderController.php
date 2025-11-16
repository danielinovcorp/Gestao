<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\DocService;

class PurchaseOrderController extends Controller
{
	public function index(Request $request)
	{
		$query = PurchaseOrder::with([
			'fornecedor' => fn($q) => $q->select('id', 'nome')->whereNull('deleted_at'),
			'origem' => fn($q) => $q->select('id', 'numero'),
		])
			->select('id', 'numero', 'fornecedor_id', 'origem_id', 'estado', 'total', 'data_encomenda', 'created_at')
			->latest();

		// FILTRO: PESQUISA
		if ($request->filled('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('encomendas_fornecedores.numero', 'like', "%{$search}%")
					->orWhere('f.nome', 'like', "%{$search}%");
			});
		}

		// FILTRO: ESTADO
		if ($request->filled('estado') && in_array($request->estado, ['rascunho', 'pendente', 'fechado', 'paga'])) {
			$query->where('encomendas_fornecedores.estado', $request->estado);
		}

		$orders = $query->paginate(15)->through(fn($o) => [
			'id'            => $o->id,
			'numero'        => $o->numero,
			'fornecedor' => [
				'nome' => $o->fornecedor?->nome ?? 'Sem Fornecedor'
			],
			'origem_numero' => $o->origem?->numero,
			'total'         => (float) $o->total,
			'estado'        => $o->estado,
			'data'          => $o->data_encomenda?->format('Y-m-d')
				?? $o->created_at->format('Y-m-d'),
		]);

		return Inertia::render('Encomendas/Fornecedores/Index', [
			'orders'  => $orders,
			'filters' => $request->only(['search', 'estado'])
		]);
	}

	/**
	 * FECHAR ENCOMENDA FORNECEDOR
	 */
	public function close(PurchaseOrder $order, SequenceService $seq)
	{
		if ($order->estado === 'fechado') {
			return back()->with('info', 'A encomenda já está fechada.');
		}

		$numero = $order->numero ?? $seq->next('purchase_orders_' . now()->year, 'EF', 4);

		$order->update([
			'estado'         => 'fechado',
			'data_encomenda' => now(),
			'numero'         => $numero,
		]);

		return back()->with('success', "Encomenda de fornecedor fechada: {$numero}");
	}

	/**
	 * GERAR PDF
	 */
	public function pdf(PurchaseOrder $order, DocService $docService)
	{
		$order->load([
			'fornecedor:id,nome,morada,codigo_postal,localidade,nif_enc',
			'linhas.artigo:id,referencia',
			'origem:id,numero'
		]);

		$view = view('pdf.encomenda-fornecedor', ['order' => $order])->render();

		$dompdf = app('dompdf.wrapper');
		$dompdf->loadHTML($view)->setPaper('a4', 'portrait');

		$pdfContent = $dompdf->output();

		$docService->storeGenerated(
			pdfContent: $pdfContent,
			title: "Encomenda Fornecedor #{$order->numero}",
			documentableType: PurchaseOrder::class,
			documentableId: $order->id,
			userId: auth()->id(),
			meta: [
				'tags' => ['encomenda', 'fornecedor', "fornecedor:{$order->fornecedor->nome}"],
				'notes' => "Gerado em " . now()->format('d/m/Y H:i')
			]
		);

		return response($pdfContent, 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="Encomenda-Fornecedor-' . $order->numero . '.pdf"'
		]);
	}

	/**
	 * MARCAR COMO PAGA
	 */
	public function markPaid(PurchaseOrder $order)
	{
		if ($order->estado !== 'fechado') {
			return back()->with('error', 'Apenas encomendas fechadas podem ser marcadas como pagas.');
		}

		$order->update(['estado' => 'paga']);

		return back()->with('success', 'Encomenda marcada como paga.');
	}
}
