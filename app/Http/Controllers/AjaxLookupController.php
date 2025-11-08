<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AjaxLookupController extends Controller
{
	/**
	 * Pesquisa Clientes (Entidades) e formata o resultado para o Async Select.
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function clientes(Request $request)
	{
		try {
			$q = $request->query('q', '');

			$results = DB::table('entidades')
				->where('is_cliente', 1)
				->where('estado', 'ativo')
				->when($q, fn($query) => $query->where(function ($w) use ($q) {
					$w->where('nome', 'like', "%{$q}%");
					// CORRIGIDO: Removido o OR WHERE em 'nif', pois a coluna 'nif' não existe
					// e a pesquisa LIKE não funciona em 'nif_enc' ou 'nif_hash'.
					// $w->orWhere('nif_enc', 'like', "%{$q}%");
				}))
				// CORRIGIDO: Seleciona 'nif_enc' e renomeia para 'nif' para compatibilidade com o frontend.
				->select('id', 'nome', 'nif_enc as nif')
				->limit(20)
				->get()
				// Mapeamento para o formato (value, label)
				->map(fn($cliente) => [
					'value' => $cliente->id,
					// Uso de ?? '' para evitar erros se 'nif' for NULL
					'label' => ($cliente->nome ?? 'Nome Desconhecido') . (($cliente->nif ?? '') ? ' (NIF: ' . $cliente->nif . ')' : ''),
					'nome' => $cliente->nome,
					'nif' => $cliente->nif,
				]);

			Log::info('AjaxLookup clientes OK', ['q' => $q, 'count' => $results->count()]);

			return response()->json($results);
		} catch (\Exception $e) {
			Log::error('Erro AjaxLookup clientes', [
				'error' => $e->getMessage(),
				'line' => $e->getLine(),
				'file' => $e->getFile()
			]);
			return response()->json(['error' => 'Erro interno ao procurar clientes.'], 500);
		}
	}

	/**
	 * Pesquisa Fornecedores (Entidades).
	 */
	public function fornecedores(Request $request)
	{
		try {
			$q = $request->query('q', '');

			$results = DB::table('entidades')
				->where('is_fornecedor', 1)
				->where('estado', 'ativo')
				->when($q, fn($query) => $query->where(function ($w) use ($q) {
					$w->where('nome', 'like', "%{$q}%");
					// CORRIGIDO: Removido o OR WHERE em 'nif'
				}))
				// CORRIGIDO: Seleciona 'nif_enc' e renomeia para 'nif'.
				->select('id', 'nome', 'nif_enc as nif')
				->limit(20)
				->get()
				->map(fn($fornecedor) => [
					'value' => $fornecedor->id,
					'label' => ($fornecedor->nome ?? 'Nome Desconhecido') . (($fornecedor->nif ?? '') ? ' (NIF: ' . $fornecedor->nif . ')' : ''),
				]);

			return response()->json($results);
		} catch (\Exception $e) {
			Log::error('Erro AjaxLookup fornecedores', ['error' => $e->getMessage()]);
			return response()->json(['error' => 'Erro interno ao procurar fornecedores.'], 500);
		}
	}

	/**
	 * Pesquisa Artigos e formata o resultado para o Async Select.
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function artigos(Request $request)
	{
		try {
			$q = $request->query('q', '');

			$results = DB::table('artigos')
				->where('estado', 'ativo')
				->when($q, fn($query) => $query->where(function ($w) use ($q) {
					$w->where('nome', 'like', "%{$q}%")
						// CRÍTICO: 'referencia' existe, mantido.
						->orWhere('referencia', 'like', "%{$q}%");
				}))
				// CORRIGIDO: Seleciona 'preco' e renomeia para 'preco_venda'
				->select('id', 'nome', 'referencia', 'preco as preco_venda')
				->limit(20)
				->get()
				// Mapeamento para o formato (value, label)
				->map(fn($artigo) => [
					'value' => $artigo->id,
					// Uso de ?? '' para evitar erros se 'referencia' ou 'nome' for NULL
					'label' => ($artigo->referencia ?? 'S/Ref.') . ' - ' . ($artigo->nome ?? 'S/Nome'),
					// O campo 'preco_venda' existe aqui devido ao alias acima
					'referencia' => $artigo->referencia,
					'preco_venda' => $artigo->preco_venda,
				]);

			return response()->json($results);
		} catch (\Exception $e) {
			Log::error('Erro AjaxLookup artigos', ['error' => $e->getMessage()]);
			return response()->json(['error' => 'Erro interno ao procurar artigos.'], 500);
		}
	}
}
