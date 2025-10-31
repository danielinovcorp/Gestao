<?php

namespace App\Mail;

use App\Models\FornecedorFatura;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ComprovativoPagamentoFornecedorMailable extends Mailable
{
	use Queueable, SerializesModels;

	public function __construct(public FornecedorFatura $fatura) {}

	public function build()
	{
		$assunto = 'Comprovativo de Pagamento - Fatura ' . $this->fatura->numero;

		$mail = $this->subject($assunto)
			->view('emails.comprovativo-fornecedor', [
				'fatura' => $this->fatura,
			]);

		if ($this->fatura->comprovativo_path && Storage::disk('private')->exists($this->fatura->comprovativo_path)) {
			$mail->attach(Storage::disk('private')->path($this->fatura->comprovativo_path));
		}

		return $mail;
	}
}
