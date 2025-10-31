<?php

namespace App\Http\Controllers;

use App\Models\Artigo;
use App\Models\Entidade;
use Illuminate\Http\Request;

class AjaxLookupController extends Controller
{
	public function clientes(Request $request)
	{
		$q = $request->string('q')->toString();
		return Entidade::query()
			->where('tipo_cliente', true) // ajuste ao seu campo boolean/enum
			->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
				$w->where('nome', 'like', "%$q%")->orWhere('nif', 'like', "%$q%");
			}))
			->limit(20)
			->get(['id', 'nome', 'nif']);
	}

	public function fornecedores(Request $request)
	{
		$q = $request->string('q')->toString();
		return Entidade::query()
			->where('tipo_fornecedor', true)
			->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
				$w->where('nome', 'like', "%$q%")->orWhere('nif', 'like', "%$q%");
			}))
			->limit(20)
			->get(['id', 'nome', 'nif']);
	}

	public function artigos(Request $request)
	{
		$q = $request->string('q')->toString();
		return Artigo::query()
			->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
				$w->where('nome', 'like', "%$q%")
					->orWhere('referencia', 'like', "%$q%");
			}))
			->limit(20)
			->get(['id', 'nome', 'referencia', 'preco_venda']);
	}
}
