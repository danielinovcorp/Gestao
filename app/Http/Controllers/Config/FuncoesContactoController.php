<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FuncoesContactoController extends Controller
{
	public function index(Request $request)
	{
		$q = trim((string) $request->get('q', ''));
		$perPage = (int) ($request->integer('per_page') ?: 15);

		$funcoes = DB::table('funcoes_contacto')
			->select(['id', 'nome']) // mantemos simples p/ bater com tua tabela
			->when($q, fn($qr) => $qr->where('nome', 'like', "%{$q}%"))
			->orderBy('nome')
			->paginate($perPage)
			->appends($request->query());

		return Inertia::render('Config/FuncoesContacto/Index', [
			'items'   => $funcoes,
			'filters' => ['q' => $q, 'per_page' => $perPage],
		]);
	}

	public function store(Request $request)
	{
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:150', Rule::unique('funcoes_contacto', 'nome')],
		]);

		$id = DB::table('funcoes_contacto')->insertGetId(['nome' => $data['nome']]);

		if (function_exists('activity')) {
			activity('config.funcoes_contacto')->withProperties(['id' => $id])->log('create');
		}

		return back()->with('success', 'Função criada.');
	}

	public function update(Request $request, int $funcao)
	{
		$data = $request->validate([
			'nome' => ['required', 'string', 'max:150', Rule::unique('funcoes_contacto', 'nome')->ignore($funcao)],
		]);

		DB::table('funcoes_contacto')->where('id', $funcao)->update(['nome' => $data['nome']]);

		if (function_exists('activity')) {
			activity('config.funcoes_contacto')->withProperties(['id' => $funcao])->log('update');
		}

		return back()->with('success', 'Função atualizada.');
	}

	public function destroy(int $funcao)
	{
		DB::table('funcoes_contacto')->where('id', $funcao)->delete();

		if (function_exists('activity')) {
			activity('config.funcoes_contacto')->withProperties(['id' => $funcao])->log('delete');
		}

		return back()->with('success', 'Função removida.');
	}
}
