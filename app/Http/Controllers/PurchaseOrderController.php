<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
	public function index()
	{
		$orders = PurchaseOrder::with('fornecedor')
			->latest()
			->paginate(15)
			->through(fn($o) => [
				'id'        => $o->id,
				'numero'    => $o->numero,
				'fornecedor' => $o->fornecedor?->nome,
				'total'     => (float) $o->total,
				'estado'    => $o->estado,
				'data'      => optional($o->created_at)->format('Y-m-d'),
				'origem'    => $o->sales_order_id, // id da encomenda cliente origem (se houver)
			]);

		return Inertia::render('Encomendas/Fornecedores/Index', [
			'orders' => $orders,
		]);
	}

	// opcional: criar manualmente uma EF (sem proposta/encomenda cliente)
	public function store(Request $r, SequenceService $seq)
	{
		$data = $r->validate([
			'fornecedor_id' => ['required', 'exists:entidades,id'],
			'linhas'        => ['required', 'array', 'min:1'],
			'linhas.*.artigo_id'  => ['required', 'exists:artigos,id'],
			'linhas.*.descricao'  => ['nullable', 'string'],
			'linhas.*.quantidade' => ['required', 'numeric', 'min:0.001'],
			'linhas.*.preco'      => ['required', 'numeric', 'min:0'],
			'linhas.*.iva_id'     => ['nullable', 'exists:ivas,id'],
		]);

		// … criar purchase_order + lines (similar ao SalesOrderController),
		// número via $seq->next('purchase_orders_' . now()->year, 'EF', 4)
		// deixei o método como placeholder caso vás permitir criação direta.
		return back()->with('success', 'Encomenda de fornecedor criada.');
	}

	public function markPaid(PurchaseOrder $purchaseOrder)
	{
		$purchaseOrder->update(['estado' => 'paga']);
		return back()->with('success', 'Marcada como paga.');
	}
}
