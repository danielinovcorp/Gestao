<?php

namespace App\Http\Controllers;

use App\Models\Artigo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArtigoController extends Controller
{
	public function index(Request $r)
	{
		$q = Artigo::query()
			->when($r->q, function ($qq) use ($r) {
				$s = trim($r->q);
				$qq->where(function ($w) use ($s) {
					$w->where('sku', 'like', "%{$s}%")
						->orWhere('descricao', 'like', "%{$s}%");
				});
			})
			->when($r->ativo !== null, fn($qq) => $qq->where('ativo', (bool)$r->boolean('ativo')));

		return $q->latest()->paginate(15);
	}

	public function store(Request $r)
	{
		$data = $r->validate([
			'sku'        => ['required', 'string', 'max:100', 'unique:artigos,sku'],
			'descricao'  => ['required', 'string', 'max:255'],
			'preco'      => ['required', 'numeric', 'min:0'],
			'iva'        => ['sometimes', 'integer', 'in:0,6,13,23'], // ajuste conforme tua regra
			'unidade'    => ['sometimes', 'string', 'max:20'],
			'ativo'      => ['sometimes', 'boolean'],
		]);

		$a = Artigo::create($data);
		activity()->performedOn($a)->withProperties($data)->log('criou artigo');

		return response()->json($a, 201);
	}

	public function show(Artigo $artigo)
	{
		return $artigo;
	}

	public function update(Request $r, Artigo $artigo)
	{
		$data = $r->validate([
			'sku'        => ['sometimes', 'string', 'max:100', Rule::unique('artigos', 'sku')->ignore($artigo->id)],
			'descricao'  => ['sometimes', 'string', 'max:255'],
			'preco'      => ['sometimes', 'numeric', 'min:0'],
			'iva'        => ['sometimes', 'integer', 'in:0,6,13,23'],
			'unidade'    => ['sometimes', 'string', 'max:20'],
			'ativo'      => ['sometimes', 'boolean'],
		]);

		$artigo->update($data);
		activity()->performedOn($artigo)->withProperties($data)->log('editou artigo');

		return $artigo;
	}

	public function destroy(Artigo $artigo)
	{
		$artigo->delete();
		activity()->performedOn($artigo)->log('removeu artigo');
		return response()->noContent();
	}
}
