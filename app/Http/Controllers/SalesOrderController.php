<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Entidade;
use App\Services\OrderToSupplierOrdersService;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesOrderController extends Controller
{
	public function index()
	{
		$orders = SalesOrder::with('cliente')
			->latest()
			->paginate(15)
			->through(fn($o) => [
				'id'      => $o->id,
				'data'    => optional($o->data_proposta ?? $o->created_at)->format('Y-m-d'),
				'numero'  => $o->numero,
				'validade' => optional($o->validade)->format('Y-m-d'),
				'cliente' => $o->cliente?->nome,
				'total'   => (float) $o->total,
				'estado'  => $o->estado,
			]);

		return Inertia::render('Encomendas/Clientes/Index', [
			'orders' => $orders,
		]);
	}

	public function create()
	{
		return Inertia::render('Encomendas/Clientes/Form', [
			'clientes'    => Entidade::where('is_cliente', 1)->orderBy('nome')->get(['id', 'nome']),
			'fornecedores' => Entidade::where('is_fornecedor', 1)->orderBy('nome')->get(['id', 'nome']),
		]);
	}

	public function store(Request $r, SequenceService $seq)
	{
		$data = $r->validate([
			'cliente_id'            => ['required', 'exists:entidades,id'],
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
		$order->validade     = $data['validade'] ?? null;
		$order->estado       = $data['estado'];
		$order->total        = 0;
		$order->save();

		$total = 0;
		foreach ($data['linhas'] as $l) {
			$line = new SalesOrderLine([
				'artigo_id'    => $l['artigo_id'],
				'descricao'    => $l['descricao'] ?? null,
				'quantidade'   => $l['quantidade'],
				'preco'        => $l['preco'],
				'iva_id'       => $l['iva_id'] ?? null,
				'fornecedor_id' => $l['fornecedor_id'] ?? null,
			]);
			$line->sales_order_id = $order->id;
			$line->total = round($line->quantidade * $line->preco, 2); // impostos simplificados por agora
			$line->save();
			$total += $line->total;
		}

		if ($order->estado === 'fechado') {
			// usa tua tabela sequences (key, next) com prefixo EC
			$order->numero        = $seq->next('sales_orders_' . now()->year, 'EC', 4);
			$order->data_proposta = now();
		}

		$order->total = $total;
		$order->save();

		return redirect()->route('encomendas.clientes.index')->with('success', 'Encomenda guardada.');
	}

	public function close(SalesOrder $order, SequenceService $seq)
	{
		if ($order->estado === 'fechado') {
			return back()->with('success', 'Já está fechada.');
		}

		$order->estado        = 'fechado';
		$order->data_proposta = now();
		$order->numero        = $order->numero ?: $seq->next('sales_orders_' . now()->year, 'EC', 4);
		$order->save();

		return back()->with('success', 'Encomenda fechada.');
	}

	public function convertToSupplierOrders(SalesOrder $order, OrderToSupplierOrdersService $svc)
	{
		if ($order->estado !== 'fechado') {
			return back()->with('error', 'Só é possível converter uma encomenda fechada.');
		}

		$count = $svc->convert($order);
		return back()->with('success', "Geradas {$count} encomendas de fornecedor.");
	}

	public function destroy(SalesOrder $order)
	{
		if ($order->estado === 'fechado') {
			return back()->with('error', 'Não é possível apagar uma encomenda fechada.');
		}
		$order->delete();
		return back()->with('success', 'Encomenda removida.');
	}
}
