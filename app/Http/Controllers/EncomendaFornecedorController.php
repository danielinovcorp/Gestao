<?php

// app/Http/Controllers/EncomendaFornecedorController.php

namespace App\Http\Controllers;

use App\Models\EncomendaFornecedor;
use App\Models\EncomendaFornecedorLinha;
use App\Models\Entidade;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EncomendaFornecedorController extends Controller
{
	/**
	 * Listagem com DataTable
	 */
	public function index()
	{
		$encomendas = EncomendaFornecedor::with(['fornecedor:id,nome', 'origem:id,numero'])
			->select([
				'id',
				'numero',
				'data_encomenda as data',
				'fornecedor_id',
				'estado',
				'total',
				DB::raw('(SELECT numero FROM encomenda_clientes WHERE id = encomenda_fornecedores.encomenda_cliente_id) as origem_numero')
			])
			->paginate(15);

		return Inertia::render('Encomendas/Fornecedores/Index', [
			'orders' => $encomendas
		]);
	}

	/**
	 * Fechar encomenda de fornecedor
	 */
	public function fechar($id)
	{
		$ef = EncomendaFornecedor::findOrFail($id);

		if ($ef->estado === 'fechado') {
			return back()->with('error', 'Encomenda já está fechada.');
		}

		$ef->update([
			'estado' => 'fechado',
			'numero' => $ef->numero ?? EncomendaFornecedor::gerarNumero(),
			'data_encomenda' => now()->format('Y-m-d'),
		]);

		return back()->with('success', 'Encomenda fechada com sucesso.');
	}

	/**
	 * PDF da Encomenda Fornecedor
	 */
	public function pdf($id)
	{
		$encomenda = EncomendaFornecedor::with(['fornecedor', 'linhas.artigo'])->findOrFail($id);

		$pdf = app('dompdf.wrapper');
		$pdf->loadView('pdf.encomenda-fornecedor', compact('encomenda'));
		return $pdf->download('Encomenda-Fornecedor-' . $encomenda->numero . '.pdf');
	}

	/**
	 * Gera número sequencial: EF-2025-0001
	 */
	public static function gerarNumero()
	{
		$ano = date('Y');
		$ultimo = EncomendaFornecedor::whereYear('created_at', $ano)->max('numero');
		$proximo = $ultimo ? $ultimo + 1 : 1;
		return $proximo;
	}
}
