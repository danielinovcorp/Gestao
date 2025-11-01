<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PaisesController extends Controller
{
	// LISTAR
	public function index(Request $request)
	{
		$q = trim((string) $request->get('q', ''));
		$perPage = (int) ($request->integer('per_page') ?: 15);

		// Campos mínimos garantidos: id, nome
		$query = DB::table('paises')
			->select(['id', 'nome'])
			->when($q, fn($qr) => $qr->where('nome', 'like', "%{$q}%"))
			->orderBy('nome');

		// paginação simples
		$paises = $query->paginate($perPage)->appends($request->query());

		return Inertia::render('Config/Paises/Index', [
			'items'   => $paises,
			'filters' => ['q' => $q, 'per_page' => $perPage],
		]);
	}

	// CRIAR
	public function store(Request $request)
	{
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:150', Rule::unique('paises', 'nome')],
		]);

		$id = DB::table('paises')->insertGetId([
			'nome' => $data['nome'],
		]);

		// (opcional) activity log
		if (function_exists('activity')) {
			activity('config.paises')->withProperties(['id' => $id])->log('create');
		}

		return back()->with('success', 'País criado.');
	}

	// ATUALIZAR
	public function update(Request $request, int $pais)
	{
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:150', Rule::unique('paises', 'nome')->ignore($pais)],
		]);

		DB::table('paises')->where('id', $pais)->update([
			'nome' => $data['nome'],
		]);

		if (function_exists('activity')) {
			activity('config.paises')->withProperties(['id' => $pais])->log('update');
		}

		return back()->with('success', 'País atualizado.');
	}

	// REMOVER
	public function destroy(int $pais)
	{
		DB::table('paises')->where('id', $pais)->delete();

		if (function_exists('activity')) {
			activity('config.paises')->withProperties(['id' => $pais])->log('delete');
		}

		return back()->with('success', 'País removido.');
	}
}
