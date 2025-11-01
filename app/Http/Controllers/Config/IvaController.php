<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IvaController extends Controller
{
	public function index(Request $request)
	{
		$q       = trim((string) $request->get('q', ''));
		$estado  = $request->get('estado');
		$perPage = (int) ($request->integer('per_page') ?: 15);

		// Descobrir colunas reais
		$hasNome      = Schema::hasColumn('iva', 'nome');
		$hasDesc      = Schema::hasColumn('iva', 'descricao');
		$hasPercent   = Schema::hasColumn('iva', 'percentagem');
		$hasTaxa      = Schema::hasColumn('iva', 'taxa');
		$hasEstado    = Schema::hasColumn('iva', 'estado');

		$labelCol    = $hasNome ? 'nome' : ($hasDesc ? 'descricao' : null);
		$percentCol  = $hasPercent ? 'percentagem' : ($hasTaxa ? 'taxa' : null);

		$labelExpr   = $labelCol ? $labelCol : "'IVA'";
		$percentExpr = $percentCol ? $percentCol : "NULL";

		$query = DB::table('iva')
			->select([
				'id',
				DB::raw("$labelExpr as label"),
				DB::raw("$percentExpr as percentagem"),
			])
			->when($hasEstado, fn($q2) => $q2->addSelect('estado'))
			->when($q && $labelCol, fn($qr) => $qr->where($labelCol, 'like', "%{$q}%"))
			->when($hasEstado && in_array($estado, ['ativo', 'inativo'], true), fn($qr) => $qr->where('estado', $estado))
			->orderByRaw($labelExpr);

		$items = $query->paginate($perPage)->appends($request->query());

		return Inertia::render('Config/IVA/Index', [
			'items'       => $items,
			'filters'     => ['q' => $q, 'estado' => $estado, 'per_page' => $perPage],
			'meta'        => [
				'hasEstado'   => $hasEstado,
				'labelCol'    => $labelCol,     // pode ser null
				'percentCol'  => $percentCol,   // pode ser null
			],
		]);
	}

	public function store(Request $request)
	{
		// Descobrir colunas reais
		$hasNome      = Schema::hasColumn('iva', 'nome');
		$hasDesc      = Schema::hasColumn('iva', 'descricao');
		$hasPercent   = Schema::hasColumn('iva', 'percentagem');
		$hasTaxa      = Schema::hasColumn('iva', 'taxa');
		$hasEstado    = Schema::hasColumn('iva', 'estado');

		$labelCol   = $hasNome ? 'nome' : ($hasDesc ? 'descricao' : null);
		$percentCol = $hasPercent ? 'percentagem' : ($hasTaxa ? 'taxa' : null);

		// validação (label e percent podem não existir na BD → guardo o que existir)
		$rules = [
			'label'      => ['required', 'string', 'max:150'],
			'percentagem' => ['required', 'numeric', 'min:0'],
		];
		if ($hasEstado) {
			$rules['estado'] = ['required', 'in:ativo,inativo'];
		}

		$data = $request->validate($rules);

		$insert = [];
		if ($labelCol)   $insert[$labelCol]   = $data['label'];
		if ($percentCol) $insert[$percentCol] = $data['percentagem'];
		if ($hasEstado)  $insert['estado']    = $data['estado'];
		$insert['created_at'] = now();
		$insert['updated_at'] = now();

		// unique opcional se existir labelCol
		if ($labelCol) {
			$request->validate([
				'label' => [Rule::unique('iva', $labelCol)],
			]);
		}

		$id = DB::table('iva')->insertGetId($insert);

		if (function_exists('activity')) {
			activity('config.iva')->withProperties(['id' => $id])->log('create');
		}

		return back()->with('success', 'IVA criado.');
	}

	public function update(Request $request, int $iva)
	{
		$hasNome      = Schema::hasColumn('iva', 'nome');
		$hasDesc      = Schema::hasColumn('iva', 'descricao');
		$hasPercent   = Schema::hasColumn('iva', 'percentagem');
		$hasTaxa      = Schema::hasColumn('iva', 'taxa');
		$hasEstado    = Schema::hasColumn('iva', 'estado');

		$labelCol   = $hasNome ? 'nome' : ($hasDesc ? 'descricao' : null);
		$percentCol = $hasPercent ? 'percentagem' : ($hasTaxa ? 'taxa' : null);

		$rules = [
			'label'      => ['required', 'string', 'max:150'],
			'percentagem' => ['required', 'numeric', 'min:0'],
		];
		if ($hasEstado) {
			$rules['estado'] = ['required', 'in:ativo,inativo'];
		}

		$data = $request->validate($rules);

		// unique opcional
		if ($labelCol) {
			$request->validate([
				'label' => [Rule::unique('iva', $labelCol)->ignore($iva)],
			]);
		}

		$update = [];
		if ($labelCol)   $update[$labelCol]   = $data['label'];
		if ($percentCol) $update[$percentCol] = $data['percentagem'];
		if ($hasEstado)  $update['estado']    = $data['estado'];
		$update['updated_at'] = now();

		DB::table('iva')->where('id', $iva)->update($update);

		if (function_exists('activity')) {
			activity('config.iva')->withProperties(['id' => $iva])->log('update');
		}

		return back()->with('success', 'IVA atualizado.');
	}

	public function destroy(int $iva)
	{
		DB::table('iva')->where('id', $iva)->delete();

		if (function_exists('activity')) {
			activity('config.iva')->withProperties(['id' => $iva])->log('delete');
		}

		return back()->with('success', 'IVA removido.');
	}
}
