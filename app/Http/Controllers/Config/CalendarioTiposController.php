<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CalendarioTiposController extends Controller
{
	public function index(Request $request)
	{
		$q = trim((string) $request->get('q', ''));
		$perPage = (int) ($request->integer('per_page') ?: 15);

		$tipos = DB::table('calendario_tipos')
			->select(['id', 'nome', 'cor_hex'])
			->when($q, fn($qr) => $qr->where('nome', 'like', "%{$q}%"))
			->orderBy('nome')
			->paginate($perPage)
			->appends($request->query());

		return Inertia::render('Config/Calendario/Tipos/Index', [
			'items'   => $tipos,
			'filters' => ['q' => $q, 'per_page' => $perPage],
		]);
	}

	public function store(Request $request)
	{
		$data = $request->validate([
			'nome'    => ['required', 'string', 'max:150', Rule::unique('calendario_tipos', 'nome')],
			'cor_hex' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
		]);

		$id = DB::table('calendario_tipos')->insertGetId([
			'nome' => $data['nome'],
			'cor_hex' => $data['cor_hex'] ?? null,
		]);

		if (function_exists('activity')) {
			activity('config.calendario.tipos')->withProperties(['id' => $id])->log('create');
		}

		return back()->with('success', 'Tipo criado.');
	}

	public function update(Request $request, int $tipo)
	{
		$data = $request->validate([
			'nome'    => ['required', 'string', 'max:150', Rule::unique('calendario_tipos', 'nome')->ignore($tipo)],
			'cor_hex' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
		]);

		DB::table('calendario_tipos')->where('id', $tipo)->update([
			'nome' => $data['nome'],
			'cor_hex' => $data['cor_hex'] ?? null,
		]);

		if (function_exists('activity')) {
			activity('config.calendario.tipos')->withProperties(['id' => $tipo])->log('update');
		}

		return back()->with('success', 'Tipo atualizado.');
	}

	public function destroy(int $tipo)
	{
		DB::table('calendario_tipos')->where('id', $tipo)->delete();

		if (function_exists('activity')) {
			activity('config.calendario.tipos')->withProperties(['id' => $tipo])->log('delete');
		}

		return back()->with('success', 'Tipo removido.');
	}
}
