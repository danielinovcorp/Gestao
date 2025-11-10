<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFornecedorFaturaRequest;
use App\Http\Requests\UpdateFornecedorFaturaRequest;
use App\Mail\ComprovativoPagamentoFornecedorMailable;
use App\Models\Entidade;
use App\Models\EncomendaFornecedor;
use App\Models\FornecedorFatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FornecedorFaturaController extends Controller
{
	public function index(Request $request)
	{
		$this->authorize('viewAny', FornecedorFatura::class);

		$query = FornecedorFatura::query()
			->with(['fornecedor:id,nome,email_enc'])
			->when($request->filled('estado'), fn($q) => $q->where('estado', $request->estado))
			->when($request->filled('fornecedor'), fn($q) => $q->where('fornecedor_id', $request->fornecedor))
			->orderByDesc('data_fatura')
			->orderByDesc('id');

		// Buscar fornecedores - usar is_fornecedor = 1
		$fornecedores = Entidade::query()
			->where('is_fornecedor', 1)
			->where('estado', 'ativo')
			->orderBy('nome')
			->get(['id', 'nome', 'email_enc']);

		// Buscar encomendas - verificar se há encomendas fechadas
		try {
			$encomendas = EncomendaFornecedor::query()
				->where('estado', 'fechado')
				->orderByDesc('data_encomenda')
				->limit(200)
				->get(['id', 'numero']);

			// Se não há encomendas fechadas, buscar as últimas encomendas
			if ($encomendas->isEmpty()) {
				$encomendas = EncomendaFornecedor::query()
					->orderByDesc('data_encomenda')
					->limit(50)
					->get(['id', 'numero']);
			}
		} catch (\Exception $e) {
			$encomendas = collect();
		}

		return Inertia::render('Financeiro/FaturasFornecedor/Index', [
			'filters' => $request->only(['estado', 'fornecedor']),
			'faturas' => $query->paginate(20)->through(function ($f) {
				return [
					'id' => $f->id,
					'numero' => $f->numero,
					'data_fatura' => $f->data_fatura->format('d/m/Y'),
					'data_vencimento' => $f->data_vencimento?->format('d/m/Y'),
					'fornecedor_id' => $f->fornecedor_id,
					'fornecedor' => $f->fornecedor?->nome,
					'encomenda_fornecedor_id' => $f->encomenda_fornecedor_id,
					'encomenda' => $f->encomendaFornecedor?->numero ?? null,
					'valor_total' => number_format($f->valor_total, 2, ',', '.'),
					'valor_total_raw' => (float) $f->valor_total,
					'estado' => $f->estado,
					'documento_url' => $f->documento_url,
					'comprovativo_url' => $f->comprovativo_url,
				];
			}),
			'fornecedores' => $fornecedores,
			'encomendas' => $encomendas,
		]);
	}

	public function create()
	{
		$this->authorize('create', FornecedorFatura::class);

		return Inertia::render('Financeiro/FaturasFornecedor/Form', [
			'mode' => 'create',
			'fatura' => null,
			'fornecedores' => Entidade::query()
				->where(fn($q) => $q->where('tipo', 'fornecedor')->orWhere('tipo', 'both'))
				->orderBy('nome')->get(['id', 'nome']),
			'encomendas' => EncomendaFornecedor::orderByDesc('id')->limit(200)->get(['id', 'numero']),
		]);
	}

	public function store(StoreFornecedorFaturaRequest $request)
	{
		$this->authorize('create', FornecedorFatura::class);

		$paths = $this->handleUploads($request);

		$fatura = null;

		DB::transaction(function () use ($request, $paths, &$fatura) {
			$fatura = FornecedorFatura::create([
				'numero' => $request->numero,
				'data_fatura' => $request->data_fatura,
				'data_vencimento' => $request->data_vencimento,
				'fornecedor_id' => $request->fornecedor_id,
				'encomenda_fornecedor_id' => $request->encomenda_fornecedor_id,
				'valor_total' => $request->valor_total,
				'documento_path' => $paths['documento'] ?? null,
				'comprovativo_path' => $paths['comprovativo'] ?? null,
				'estado' => $request->estado,
			]);

			if ($fatura->estado === 'paga') {
				$this->enviarComprovativo($fatura);
			}
		});

		return redirect()->route('financeiro.faturas-fornecedor.index')
			->with('success', 'Fatura criada com sucesso.');
	}

	public function edit(FornecedorFatura $fornecedor_fatura)
	{
		$this->authorize('update', $fornecedor_fatura);

		return Inertia::render('Financeiro/FaturasFornecedor/Form', [
			'mode' => 'edit',
			'fatura' => [
				'id' => $fornecedor_fatura->id,
				'numero' => $fornecedor_fatura->numero,
				'data_fatura' => optional($fornecedor_fatura->data_fatura)->format('Y-m-d'),
				'data_vencimento' => optional($fornecedor_fatura->data_vencimento)->format('Y-m-d'),
				'fornecedor_id' => $fornecedor_fatura->fornecedor_id,
				'encomenda_fornecedor_id' => $fornecedor_fatura->encomenda_fornecedor_id,
				'valor_total' => (float) $fornecedor_fatura->valor_total,
				'estado' => $fornecedor_fatura->estado,
				'documento_url' => $fornecedor_fatura->documento_url,
				'comprovativo_url' => $fornecedor_fatura->comprovativo_url,
			],
			'fornecedores' => Entidade::query()
				->where(fn($q) => $q->where('tipo', 'fornecedor')->orWhere('tipo', 'both'))
				->orderBy('nome')->get(['id', 'nome']),
			'encomendas' => EncomendaFornecedor::orderByDesc('id')->limit(200)->get(['id', 'numero']),
		]);
	}

	public function update(UpdateFornecedorFaturaRequest $request, FornecedorFatura $fornecedor_fatura)
	{
		$this->authorize('update', $fornecedor_fatura);

		$paths = $this->handleUploads($request, $fornecedor_fatura);

		DB::transaction(function () use ($request, $fornecedor_fatura, $paths) {
			$estadoAnterior = $fornecedor_fatura->estado;

			$fornecedor_fatura->update([
				'numero' => $request->numero,
				'data_fatura' => $request->data_fatura,
				'data_vencimento' => $request->data_vencimento,
				'fornecedor_id' => $request->fornecedor_id,
				'encomenda_fornecedor_id' => $request->encomenda_fornecedor_id,
				'valor_total' => $request->valor_total,
				'documento_path' => $paths['documento'] ?? $fornecedor_fatura->documento_path,
				'comprovativo_path' => $paths['comprovativo'] ?? $fornecedor_fatura->comprovativo_path,
				'estado' => $request->estado,
			]);

			if ($estadoAnterior !== 'paga' && $fornecedor_fatura->estado === 'paga') {
				$this->enviarComprovativo($fornecedor_fatura);
			}
		});

		return back()->with('success', 'Fatura atualizada com sucesso.');
	}

	public function destroy(FornecedorFatura $fornecedor_fatura)
	{
		$this->authorize('delete', $fornecedor_fatura);
		$fornecedor_fatura->delete();

		return back()->with('success', 'Fatura removida.');
	}

	private function handleUploads(Request $request, ?FornecedorFatura $existing = null): array
	{
		$out = [];

		if ($request->hasFile('documento')) {
			if ($existing?->documento_path) Storage::disk('private')->delete($existing->documento_path);
			$out['documento'] = $request->file('documento')->store('faturas/documentos', 'private');
		}
		if ($request->hasFile('comprovativo')) {
			if ($existing?->comprovativo_path) Storage::disk('private')->delete($existing->comprovativo_path);
			$out['comprovativo'] = $request->file('comprovativo')->store('faturas/comprovativos', 'private');
		}

		return $out;
	}

	private function enviarComprovativo(FornecedorFatura $fatura): void
	{
		$emailDestino = $fatura->fornecedor?->email; // Ajusta conforme tua estrutura de contactos
		if (!$emailDestino) return;

		Mail::to($emailDestino)->queue(new ComprovativoPagamentoFornecedorMailable($fatura));

		$fatura->forceFill(['comprovativo_enviado_em' => now()])->save();
	}
}
