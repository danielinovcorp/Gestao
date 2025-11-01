<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Builder;

class ArtigosController extends Controller
{
	public function index(Request $request)
	{
		$q       = trim((string) $request->get('q', ''));
		$ivaId   = $request->integer('iva_id');
		$estado  = $request->get('estado');
		$perPage = (int) ($request->integer('per_page') ?: 15);

		$query = DB::table('artigos as a')
			->leftJoin('iva as i', 'i.id', '=', 'a.iva_id')
			->select([
				'a.id',
				'a.referencia',
				'a.nome',
				'a.descricao',
				'a.preco',
				'a.foto_path',
				'a.estado',
				'a.iva_id',
				DB::raw('COALESCE(i.nome, i.descricao) as iva_nome'),
				DB::raw('COALESCE(i.percentagem, i.taxa) as iva_percentagem'),
			])
			->when($q, fn($qr) => $qr->where(function ($w) use ($q) {
				$w->where('a.referencia', 'like', "%{$q}%")
					->orWhere('a.nome', 'like', "%{$q}%")
					->orWhere('a.descricao', 'like', "%{$q}%");
			}))
			->when($ivaId, fn($qr) => $qr->where('a.iva_id', $ivaId))
			->when(in_array($estado, ['ativo', 'inativo'], true), fn($qr) => $qr->where('a.estado', $estado))
			->orderBy('a.nome');

		$items = $query->paginate($perPage)->appends($request->query());

		// opções de IVA (apenas ativos, se tiver coluna estado)
		// --- IVA options dinâmicas (lidando com nomes diferentes de colunas) ---
		$hasNome       = Schema::hasColumn('iva', 'nome');
		$hasDescricao  = Schema::hasColumn('iva', 'descricao');
		$hasPercent    = Schema::hasColumn('iva', 'percentagem');
		$hasTaxa       = Schema::hasColumn('iva', 'taxa');
		$hasEstado     = Schema::hasColumn('iva', 'estado');

		// Label: usa o que existir
		$labelExpr = $hasNome && $hasDescricao
			? 'COALESCE(nome, descricao)'
			: ($hasNome ? 'nome' : ($hasDescricao ? 'descricao' : "'IVA'"));

		// Percentagem: usa percentagem/taxa se existirem
		$percentExpr = $hasPercent && $hasTaxa
			? 'COALESCE(percentagem, taxa)'
			: ($hasPercent ? 'percentagem' : ($hasTaxa ? 'taxa' : 'NULL'));

		$ivaQuery = DB::table('iva')
			->select([
				'id',
				DB::raw("$labelExpr as label"),
				DB::raw("$percentExpr as percentagem"),
			]);

		if ($hasEstado) {
			$ivaQuery->where('estado', 'ativo');
		}

		$ivaOptions = $ivaQuery
			->orderByRaw($labelExpr)
			->get();



		return Inertia::render('Config/Artigos/Index', [
			'items'      => $items,
			'filters'    => [
				'q' => $q,
				'iva_id' => $ivaId,
				'estado' => $estado,
				'per_page' => $perPage,
			],
			'ivaOptions' => $ivaOptions,
			'filesRoute' => route('files.private.show'), // para construir src das imagens
		]);
	}

	public function store(Request $request)
	{
		$data = $request->validate([
			'referencia'  => ['required', 'string', 'max:50', Rule::unique('artigos', 'referencia')],
			'nome'        => ['required', 'string', 'max:255'],
			'descricao'   => ['nullable', 'string'],
			'preco'       => ['required', 'numeric', 'min:0'],
			'iva_id'      => ['required', 'exists:iva,id'],
			'foto'        => ['nullable', 'image', 'max:4096'], // 4MB
			'observacoes' => ['nullable', 'string'],
			'estado'      => ['required', 'in:ativo,inativo'],
		]);

		$fotoPath = null;
		if ($request->hasFile('foto')) {
			$fotoPath = $request->file('foto')->store('artigos', 'private');
		}

		$id = DB::table('artigos')->insertGetId([
			'referencia'  => $data['referencia'],
			'nome'        => $data['nome'],
			'descricao'   => $data['descricao'] ?? null,
			'preco'       => $data['preco'],
			'iva_id'      => $data['iva_id'],
			'foto_path'   => $fotoPath,
			'observacoes' => $data['observacoes'] ?? null,
			'estado'      => $data['estado'],
			'created_at'  => now(),
			'updated_at'  => now(),
		]);

		if (function_exists('activity')) {
			activity('config.artigos')->withProperties(['id' => $id])->log('create');
		}

		return back()->with('success', 'Artigo criado.');
	}

	public function update(Request $request, int $artigo)
	{
		$data = $request->validate([
			'referencia'  => ['required', 'string', 'max:50', Rule::unique('artigos', 'referencia')->ignore($artigo)],
			'nome'        => ['required', 'string', 'max:255'],
			'descricao'   => ['nullable', 'string'],
			'preco'       => ['required', 'numeric', 'min:0'],
			'iva_id'      => ['required', 'exists:iva,id'],
			'foto'        => ['nullable', 'image', 'max:4096'],
			'observacoes' => ['nullable', 'string'],
			'estado'      => ['required', 'in:ativo,inativo'],
		]);

		$exists = DB::table('artigos')->where('id', $artigo)->first();
		$fotoPath = $exists->foto_path ?? null;

		if ($request->boolean('remove_foto') && $fotoPath) {
			Storage::disk('private')->delete($fotoPath);
			$fotoPath = null;
		}

		if ($request->hasFile('foto')) {
			if ($fotoPath) Storage::disk('private')->delete($fotoPath);
			$fotoPath = $request->file('foto')->store('artigos', 'private');
		}

		DB::table('artigos')->where('id', $artigo)->update([
			'referencia'  => $data['referencia'],
			'nome'        => $data['nome'],
			'descricao'   => $data['descricao'] ?? null,
			'preco'       => $data['preco'],
			'iva_id'      => $data['iva_id'],
			'foto_path'   => $fotoPath,
			'observacoes' => $data['observacoes'] ?? null,
			'estado'      => $data['estado'],
			'updated_at'  => now(),
		]);

		if (function_exists('activity')) {
			activity('config.artigos')->withProperties(['id' => $artigo])->log('update');
		}

		return back()->with('success', 'Artigo atualizado.');
	}

	public function destroy(int $artigo)
	{
		$row = DB::table('artigos')->where('id', $artigo)->first();
		if ($row && $row->foto_path) {
			Storage::disk('private')->delete($row->foto_path);
		}
		DB::table('artigos')->where('id', $artigo)->delete();

		if (function_exists('activity')) {
			activity('config.artigos')->withProperties(['id' => $artigo])->log('delete');
		}

		return back()->with('success', 'Artigo removido.');
	}
}
