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
		$perPage = min(100, (int) ($request->integer('per_page') ?: 15));

		// ✅ CORREÇÃO: Usando 'iso' em vez de 'codigo'
		$query = DB::table('paises')
			->select(['id', 'nome', 'iso']) // ✅ iso existe na sua tabela
			->when($q, function ($query) use ($q) {
				return $query->where('nome', 'like', "%{$q}%")
					->orWhere('iso', 'like', "%{$q}%"); // ✅ Busca também por ISO
			})
			->orderBy('nome');

		$paises = $query->paginate($perPage)->withQueryString();

		return Inertia::render('Config/Paises/Index', [
			'items'   => $paises,
			'filters' => ['q' => $q, 'per_page' => $perPage],
		]);
	}

	// CRIAR
	public function store(Request $request)
	{
		// ✅ CORREÇÃO: Validação do 'iso'
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:255', Rule::unique('paises', 'nome')],
			'iso' => ['required', 'string', 'max:2', Rule::unique('paises', 'iso')],
		]);

		try {
			$id = DB::table('paises')->insertGetId([
				'nome' => $data['nome'],
				'iso' => strtoupper($data['iso']), // ✅ Garante maiúsculas
				'created_at' => now(),
				'updated_at' => now(),
			]);

			// Activity log (opcional)
			if (function_exists('activity')) {
				activity('config.paises')
					->withProperties(['id' => $id])
					->log('created');
			}

			return redirect()->route('config.paises.index')
				->with('success', 'País criado com sucesso.');
		} catch (\Exception $e) {
			return back()->with('error', 'Erro ao criar país: ' . $e->getMessage());
		}
	}

	// ATUALIZAR
	public function update(Request $request, int $pais)
	{
		// ✅ CORREÇÃO: Validação do 'iso'
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:255', Rule::unique('paises', 'nome')->ignore($pais)],
			'iso' => ['required', 'string', 'max:2', Rule::unique('paises', 'iso')->ignore($pais)],
		]);

		try {
			$updated = DB::table('paises')->where('id', $pais)->update([
				'nome' => $data['nome'],
				'iso' => strtoupper($data['iso']), // ✅ Garante maiúsculas
				'updated_at' => now(),
			]);

			if ($updated) {
				if (function_exists('activity')) {
					activity('config.paises')
						->withProperties(['id' => $pais])
						->log('updated');
				}

				return redirect()->route('config.paises.index')
					->with('success', 'País atualizado com sucesso.');
			}

			return back()->with('error', 'País não encontrado.');
		} catch (\Exception $e) {
			return back()->with('error', 'Erro ao atualizar país: ' . $e->getMessage());
		}
	}

	// REMOVER
	public function destroy(int $pais)
	{
		try {
			// Verifica se o país está sendo usado em entidades
			$emUso = DB::table('entidades')->where('pais_id', $pais)->exists();

			if ($emUso) {
				return back()->with('error', 'Não é possível remover o país porque está sendo usado em entidades.');
			}

			$deleted = DB::table('paises')->where('id', $pais)->delete();

			if ($deleted) {
				if (function_exists('activity')) {
					activity('config.paises')
						->withProperties(['id' => $pais])
						->log('deleted');
				}

				return back()->with('success', 'País removido com sucesso.');
			}

			return back()->with('error', 'País não encontrado.');
		} catch (\Exception $e) {
			return back()->with('error', 'Erro ao remover país: ' . $e->getMessage());
		}
	}
}
