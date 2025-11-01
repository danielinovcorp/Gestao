<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmpresaController extends Controller
{
	// Mostra o formulário (singleton id=1)
	public function show(Request $request)
	{
		$row = DB::table('empresa')->where('id', 1)->first();

		return Inertia::render('Config/Empresa/Index', [
			'empresa'    => $row,
			'filesRoute' => route('files.private.show'),
		]);
	}

	// Atualiza dados + logotipo
	public function update(Request $request)
	{
		$data = $request->validate([
			'nome'          => ['nullable', 'string', 'max:255'],
			'morada'        => ['nullable', 'string', 'max:255'],
			'codigo_postal' => ['nullable', 'string', 'max:20'],
			'localidade'    => ['nullable', 'string', 'max:255'],
			'nif'           => ['nullable', 'string', 'max:32'],
			'logo'          => ['nullable', 'image', 'max:4096'], // 4MB
			'remove_logo'   => ['sometimes', 'boolean'],
		]);

		$current = DB::table('empresa')->where('id', 1)->first();
		$logoPath = $current->logo_path ?? null;

		if ($request->boolean('remove_logo') && $logoPath) {
			Storage::disk('private')->delete($logoPath);
			$logoPath = null;
		}

		if ($request->hasFile('logo')) {
			if ($logoPath) Storage::disk('private')->delete($logoPath);
			// guarda num caminho previsível
			$logoPath = $request->file('logo')->store('empresa', 'private');
		}

		DB::table('empresa')->where('id', 1)->update([
			'nome'          => $data['nome'] ?? null,
			'morada'        => $data['morada'] ?? null,
			'codigo_postal' => $data['codigo_postal'] ?? null,
			'localidade'    => $data['localidade'] ?? null,
			'nif'           => $data['nif'] ?? null,
			'logo_path'     => $logoPath,
			'updated_at'    => now(),
		]);

		if (function_exists('activity')) {
			activity('config.empresa')->log('update');
		}

		return back()->with('success', 'Dados da empresa atualizados.');
	}
}
