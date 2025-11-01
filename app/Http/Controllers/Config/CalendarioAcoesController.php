<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CalendarioAcoesController extends Controller
{
	public function index(Request $request)
	{
		$q = trim((string) $request->get('q', ''));
		$perPage = (int) ($request->integer('per_page') ?: 15);

		$acoes = DB::table('calendario_acoes')
			->select(['id', 'nome'])
			->when($q, fn($qr) => $qr->where('nome', 'like', "%{$q}%"))
			->orderBy('nome')
			->paginate($perPage)
			->appends($request->query());

		return Inertia::render('Config/Calendario/Acoes/Index', [
			'items'   => $acoes,
			'filters' => ['q' => $q, 'per_page' => $perPage],
		]);
	}

	public function store(Request $request)
	{
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:150', Rule::unique('calendario_acoes', 'nome')],
		]);

		$id = DB::table('calendario_acoes')->insertGetId(['nome' => $data['nome']]);

		if (function_exists('activity')) {
			activity('config.calendario.acoes')->withProperties(['id' => $id])->log('create');
		}

		return back()->with('success', 'Ação criada.');
	}

	public function update(Request $request, int $acao)
	{
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:150', Rule::unique('calendario_acoes', 'nome')->ignore($acao)],
		]);

		DB::table('calendario_acoes')->where('id', $acao)->update(['nome' => $data['nome']]);

		if (function_exists('activity')) {
			activity('config.calendario.acoes')->withProperties(['id' => $acao])->log('update');
		}

		return back()->with('success', 'Ação atualizada.');
	}

	public function destroy(int $acao)
	{
		DB::table('calendario_acoes')->where('id', $acao)->delete();

		if (function_exists('activity')) {
			activity('config.calendario.acoes')->withProperties(['id' => $acao])->log('delete');
		}

		return back()->with('success', 'Ação removida.');
	}
}
