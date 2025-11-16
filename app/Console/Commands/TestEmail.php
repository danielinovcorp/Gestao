<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\ComprovativoPagamentoFornecedorMailable;
use App\Models\FornecedorFatura;

class TestEmail extends Command
{
	protected $signature = 'mail:test';
	protected $description = 'Envia um email de teste usando Mailtrap';

	public function handle()
	{
		// Pega a primeira fatura (ou crie uma de teste)
		$fatura = FornecedorFatura::with('fornecedor')->first();

		if (!$fatura) {
			$this->error('Nenhuma fatura encontrada. Crie uma fatura primeiro.');
			return 1;
		}

		if (!$fatura->comprovativo_path || !file_exists(storage_path('app/private/' . $fatura->comprovativo_path))) {
			$this->error('Comprovativo não encontrado. Adicione um comprovativo à fatura.');
			return 1;
		}

		try {
			Mail::to('teste@exemplo.com')->send(new ComprovativoPagamentoFornecedorMailable($fatura));
			$this->info('Email enviado com sucesso! Verifique no Mailtrap.');
		} catch (\Exception $e) {
			$this->error('Erro ao enviar email: ' . $e->getMessage());
		}

		return 0;
	}
}
