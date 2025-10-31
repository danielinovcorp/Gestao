<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;

class OrderToSupplierOrdersService
{
	public function __construct(private SequenceService $seq) {}

	public function convert(SalesOrder $order): int
	{
		if ($order->estado !== 'fechado') {
			throw new \RuntimeException('Apenas encomendas fechadas podem ser convertidas.');
		}

		$grupos = $order->linhas()->whereNotNull('fornecedor_id')->get()
			->groupBy('fornecedor_id');

		$count = 0;

		DB::transaction(function () use ($order, $grupos, &$count) {
			foreach ($grupos as $fornecedorId => $linhas) {
				$po = new PurchaseOrder();
				$po->numero = app(SequenceService::class)->next('purchase_orders', 'EF', now()->year);
				$po->fornecedor_id = $fornecedorId;
				$po->sales_order_id = $order->id;
				$po->estado = 'aberta';
				$po->total = 0;
				$po->save();

				$total = 0;
				foreach ($linhas as $l) {
					$line = new PurchaseOrderLine();
					$line->purchase_order_id = $po->id;
					$line->artigo_id = $l->artigo_id;
					$line->descricao = $l->descricao;
					$line->quantidade = $l->quantidade;
					$line->preco = $l->preco;
					$line->iva_id = $l->iva_id;
					$line->total = $l->total;
					$line->save();
					$total += $line->total;
				}
				$po->update(['total' => $total]);
				$count++;
			}
		});

		return $count;
	}
}
