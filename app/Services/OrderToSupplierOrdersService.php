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

		$grupos = $order->linhas()
			->whereNotNull('fornecedor_id')
			->with('fornecedor:id,nome')
			->get()
			->groupBy('fornecedor_id');

		$count = 0;

		DB::transaction(function () use ($order, $grupos, &$count) {
			foreach ($grupos as $fornecedorId => $linhas) {

				// GERAR NÚMERO ANTES DO CREATE
				$numero = $this->seq->next('purchase_orders_' . now()->year, 'EF', 4);

				$po = PurchaseOrder::create([
					'fornecedor_id'  => $fornecedorId,
					'origem_id'      => $order->id,
					'estado'         => 'rascunho',
					'data_encomenda' => now()->format('Y-m-d'),
					'total'          => 0,
					'numero'         => $numero,  // ← AGORA EXISTE!
				]);

				$total = 0;
				// app/Services/OrderToSupplierOrdersService.php

				foreach ($linhas as $l) {
					PurchaseOrderLine::create([
						'encomenda_fornecedor_id' => $po->id,
						'artigo_id'               => $l->artigo_id,
						'descricao'               => $l->descricao,
						'qtd'                     => $l->quantidade,  // ← SEM ESPAÇOS!
						'preco'                   => $l->preco,
						'iva_id'                  => $l->iva_id,
						'total_linha'             => $l->total,
					]);
					$total += $l->total;
				}

				$po->update([
					'total' => $total,
					// NÃO PRECISA MAIS GERAR NÚMERO AQUI
				]);

				$count++;
			}
		});

		return $count;
	}
}
