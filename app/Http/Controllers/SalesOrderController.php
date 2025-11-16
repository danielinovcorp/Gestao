<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Entidade;
use App\Services\OrderToSupplierOrdersService;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Artigo;
use App\Services\DocService;

class SalesOrderController extends Controller
{
	public function index(Request $request)
	{
		$query = SalesOrder::with('cliente')->latest();

		// Filtros
		if ($request->filled('search')) {
			$query->where(function ($q) use ($request) {
				$q->where('numero', 'like', '%' . $request->search . '%')
					->orWhereHas('cliente', fn($q) => $q->where('nome', 'like', '%' . $request->search . '%'));
			});
		}

		// ESTADO: só aplica se for 'rascunho' ou 'fechado'
		if ($request->filled('estado') && in_array($request->estado, ['rascunho', 'fechado'])) {
			$query->where('estado', $request->estado);
		}

		$orders = $query->paginate(15)->through(fn($o) => [
			'id'      => $o->id,
			'data'    => $o->data_proposta?->format('Y-m-d') ?? $o->created_at?->format('Y-m-d'),
			'numero'  => $o->numero,
			'validade' => $o->validade?->format('Y-m-d'),
			'cliente' => $o->cliente?->nome,
			'total'   => (float) $o->total,
			'estado'  => $o->estado,
		]);

		return Inertia::render('Encomendas/Clientes/Index', [
			'orders'       => $orders,
			'filters'      => $request->only(['search', 'estado']),
			'clientes'     => Entidade::where('is_cliente', 1)->orderBy('nome')->get(['id', 'nome']),
			'fornecedores' => Entidade::where('is_fornecedor', 1)->orderBy('nome')->get(['id', 'nome']),
			'artigos'      => Artigo::orderBy('referencia')->get(['id', 'referencia', 'nome', 'preco']),
		]);
	}

	public function create()
	{
		return Inertia::render('Encomendas/Clientes/Index', [
			'clientes'     => Entidade::where('is_cliente', 1)->orderBy('nome')->get(['id', 'nome']),
			'fornecedores' => Entidade::where('is_fornecedor', 1)->orderBy('nome')->get(['id', 'nome']),
			'artigos'      => Artigo::orderBy('referencia')->get(['id', 'referencia', 'nome', 'preco']),
		]);
	}

	public function store(Request $r, SequenceService $seq)
	{
		$data = $r->validate([
			'cliente_id'            => ['required', 'exists:entidades,id'],
			'data_proposta'         => ['required', 'date'],
			'validade'              => ['nullable', 'date'],
			'estado'                => ['required', 'in:rascunho,fechado'],
			'linhas'                => ['required', 'array', 'min:1'],
			'linhas.*.artigo_id'    => ['required', 'exists:artigos,id'],
			'linhas.*.descricao'    => ['nullable', 'string'],
			'linhas.*.quantidade'   => ['required', 'numeric', 'min:0.001'],
			'linhas.*.preco'        => ['required', 'numeric', 'min:0'],
			'linhas.*.iva_id'       => ['nullable', 'exists:ivas,id'],
			'linhas.*.fornecedor_id' => ['nullable', 'exists:entidades,id'],
		]);

		$order = new SalesOrder();
		$order->cliente_id   = $data['cliente_id'];
		$order->data_proposta = $data['data_proposta'];
		$order->validade     = $data['validade'] ?? null;
		$order->estado       = $data['estado'];
		$order->total        = 0.0; // ← USE 0.0 em vez de 0
		$order->save();

		$total = 0.0; // ← USE 0.0 em vez de 0
		foreach ($data['linhas'] as $l) {
			$line = new SalesOrderLine([
				'artigo_id'    => $l['artigo_id'],
				'descricao'    => $l['descricao'] ?? null,
				'quantidade'   => (float) $l['quantidade'], // ← CAST PARA FLOAT
				'preco'        => (float) $l['preco'], // ← CAST PARA FLOAT
				'iva_id'       => $l['iva_id'] ?? null,
				'fornecedor_id' => $l['fornecedor_id'] ?? null,
			]);
			$line->sales_order_id = $order->id;
			$line->total = (float) round($line->quantidade * $line->preco, 2); // ← CAST PARA FLOAT
			$line->save();
			$total += $line->total;
		}

		if ($order->estado === 'fechado') {
			$order->numero = $seq->next('sales_orders_' . now()->year, 'EC', 4);
		}

		$order->total = (float) $total; // ← CAST PARA FLOAT
		$order->save();

		return redirect()->route('encomendas.clientes.index')->with('success', 'Encomenda guardada.');
	}

	public function close(SalesOrder $order, SequenceService $seq)
	{
		if ($order->estado === 'fechado') {
			return back()->with('info', 'Encomenda já está fechada.');
		}

		$order->update([
			'estado'        => 'fechado',
			'data_proposta' => now(),
			'numero'        => $seq->next('sales_orders_' . now()->year, 'EC', 4),
		]);

		return back()->with('success', 'Encomenda fechada com sucesso.');
	}

	public function convertToSupplierOrders(SalesOrder $order, OrderToSupplierOrdersService $svc)
	{
		if ($order->estado !== 'fechado') {
			return back()->with('error', 'Apenas encomendas fechadas podem ser convertidas.');
		}

		$count = $svc->convert($order);

		return back()->with('success', "Convertido com sucesso! Criadas {$count} encomendas de fornecedor.");
	}

	public function destroy(SalesOrder $order)
	{
		if ($order->estado === 'fechado') {
			return back()->with('error', 'Não é possível apagar uma encomenda fechada.');
		}
		$order->delete();
		return back()->with('success', 'Encomenda removida.');
	}

	public function pdf(SalesOrder $order, DocService $docService)
	{
		$order->load([
			'cliente:id,nome,morada,codigo_postal,localidade,nif_enc',
			'linhas.artigo:id,referencia',
			'linhas.fornecedor:id,nome'
		]);

		$view = view('pdf.encomenda-cliente', ['order' => $order])->render();

		$dompdf = app('dompdf.wrapper');
		$dompdf->loadHTML($view)->setPaper('a4', 'portrait');

		$pdfContent = $dompdf->output();

		$docService->storeGenerated(
			pdfContent: $pdfContent,
			title: "Encomenda Cliente #{$order->numero}",
			documentableType: SalesOrder::class,
			documentableId: $order->id,
			userId: auth()->id(),
			meta: [
				'tags' => ['encomenda', 'cliente', "cliente:{$order->cliente->nome}"],
				'notes' => "Gerado em " . now()->format('d/m/Y H:i')
			]
		);

		return response($pdfContent, 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="Encomenda-Cliente-' . $order->numero . '.pdf"'
		]);
	}
}
