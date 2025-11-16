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
use App\Services\DocService;
use Illuminate\Support\Facades\Log;

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

	public function store(StoreFornecedorFaturaRequest $request, DocService $docService)
	{
		$this->authorize('create', FornecedorFatura::class);

		$fatura = null;

		DB::transaction(function () use ($request, &$fatura, $docService) {
			$fatura = FornecedorFatura::create([
				'numero' => $request->numero,
				'data_fatura' => $request->data_fatura,
				'data_vencimento' => $request->data_vencimento,
				'fornecedor_id' => $request->fornecedor_id,
				'encomenda_fornecedor_id' => $request->encomenda_fornecedor_id,
				'valor_total' => $request->valor_total,
				'estado' => $request->estado,
			]);

			// GERA PDF AUTOMATICAMENTE
			$pdfContent = $this->generatePdf($fatura);
			$path = 'faturas/documentos/' . $fatura->id . '.pdf';
			Storage::disk('private')->put($path, $pdfContent);
			$fatura->update(['documento_path' => $path]);

			// SALVA NO ARQUIVO DIGITAL
			$docService->storeGenerated(
				pdfContent: $pdfContent,
				title: "Fatura Fornecedor #{$fatura->numero}",
				documentableType: FornecedorFatura::class,
				documentableId: $fatura->id,
				userId: auth()->id(),
				meta: [
					'tags' => ['fatura', 'fornecedor', "fornecedor:{$fatura->fornecedor->nome}"],
					'notes' => "Gerado em " . now()->format('d/m/Y H:i')
				]
			);

			// ENVIA COMPROVATIVO SE ESTADO = PAGA E HÁ FICHEIRO
			if ($fatura->estado === 'paga' && $request->hasFile('comprovativo')) {
				$comprovativoPath = $request->file('comprovativo')->store('faturas/comprovativos', 'private');
				$fatura->update(['comprovativo_path' => $comprovativoPath]);

				$this->enviarComprovativo($fatura);
			}
		});

		return redirect()->route('financeiro.faturas-fornecedor.index')
			->with('success', 'Fatura criada com sucesso.');
	}

	public function update(UpdateFornecedorFaturaRequest $request, FornecedorFatura $fornecedor_fatura, DocService $docService)
	{
		$this->authorize('update', $fornecedor_fatura);

		$estadoAnterior = $fornecedor_fatura->estado;

		DB::transaction(function () use ($request, $fornecedor_fatura, $docService, $estadoAnterior) {
			$fornecedor_fatura->update([
				'numero' => $request->numero,
				'data_fatura' => $request->data_fatura,
				'data_vencimento' => $request->data_vencimento,
				'fornecedor_id' => $request->fornecedor_id,
				'encomenda_fornecedor_id' => $request->encomenda_fornecedor_id,
				'valor_total' => $request->valor_total,
				'estado' => $request->estado,
			]);

			// REGENERA PDF
			$pdfContent = $this->generatePdf($fornecedor_fatura);
			$path = 'faturas/documentos/' . $fornecedor_fatura->id . '.pdf';
			Storage::disk('private')->put($path, $pdfContent);
			$fornecedor_fatura->update(['documento_path' => $path]);

			// ATUALIZA ARQUIVO DIGITAL
			$docService->storeGenerated(
				pdfContent: $pdfContent,
				title: "Fatura Fornecedor #{$fornecedor_fatura->numero}",
				documentableType: FornecedorFatura::class,
				documentableId: $fornecedor_fatura->id,
				userId: auth()->id(),
				meta: [
					'tags' => ['fatura', 'fornecedor'],
					'notes' => "Atualizado em " . now()->format('d/m/Y H:i')
				]
			);

			// ENVIA COMPROVATIVO SE MUDOU PARA PAGA E HÁ FICHEIRO
			if ($estadoAnterior !== 'paga' && $fornecedor_fatura->estado === 'paga' && $request->hasFile('comprovativo')) {
				$comprovativoPath = $request->file('comprovativo')->store('faturas/comprovativos', 'private');
				$fornecedor_fatura->update(['comprovativo_path' => $comprovativoPath]);

				$this->enviarComprovativo($fornecedor_fatura);
			}
		});

		return back()->with('success', 'Fatura atualizada com sucesso.');
	}

	private function enviarComprovativo(FornecedorFatura $fatura): void
	{
		try {
			\Log::info('=== INICIANDO ENVIO DE EMAIL ===');

			$emailDestino = 'teste@mailtrap.io';

			\Log::info('Destinatário: ' . $emailDestino);
			\Log::info('Fatura ID: ' . $fatura->id);
			\Log::info('Comprovativo path: ' . $fatura->comprovativo_path);

			// Verifica se o arquivo existe
			if (!$fatura->comprovativo_path || !\Illuminate\Support\Facades\Storage::disk('private')->exists($fatura->comprovativo_path)) {
				\Log::warning('Comprovativo não encontrado');
				return;
			}

			\Log::info('Enviando email...');

			// Envia o email
			\Illuminate\Support\Facades\Mail::to($emailDestino)
				->send(new \App\Mail\ComprovativoPagamentoFornecedorMailable($fatura));

			// Atualiza a fatura
			$fatura->update(['comprovativo_enviado_em' => now()]);

			\Log::info('=== EMAIL ENVIADO COM SUCESSO ===');
		} catch (\Exception $e) {
			\Log::error('=== ERRO NO ENVIO ===: ' . $e->getMessage());
			\Log::error('Stack trace: ' . $e->getTraceAsString());
		}
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

	private function generatePdf($fatura)
	{
		$fatura->load('fornecedor', 'encomendaFornecedor');
		$view = view('pdf.fatura-fornecedor', compact('fatura'))->render();
		$dompdf = app('dompdf.wrapper');
		$dompdf->loadHTML($view)->setPaper('a4', 'portrait');
		return $dompdf->output();
	}
}
