<?php

namespace App\Http\Controllers;

use App\Models\Entidade;
use Illuminate\Http\Request;

class EntidadeController extends Controller
{
	public function index(Request $r)
	{
		$query = Entidade::query()->when($r->q, fn($q) => $q->where('nome', 'like', "%{$r->q}%"));
		return $query->latest()->paginate(15);
	}
	public function store(Request $r)
	{
		$data = $r->validate(['tipo' => 'required|in:cliente,fornecedor,ambos', 'nome' => 'required|string|max:255', 'nif' => 'nullable|string|max:32']);
		$e = Entidade::create($data);
		activity()->performedOn($e)->withProperties($data)->log('criou entidade');
		return response()->json($e, 201);
	}
	public function show(Entidade $entidade)
	{
		return $entidade;
	}
	public function update(Request $r, Entidade $entidade)
	{
		$data = $r->validate(['tipo' => 'in:cliente,fornecedor,ambos', 'nome' => 'string|max:255', 'nif' => 'nullable|string|max:32']);
		$entidade->update($data);
		activity()->performedOn($entidade)->withProperties($data)->log('editou entidade');
		return $entidade;
	}
	public function destroy(Entidade $entidade)
	{
		$entidade->delete();
		activity()->performedOn($entidade)->log('removeu entidade');
		return response()->noContent();
	}
}
