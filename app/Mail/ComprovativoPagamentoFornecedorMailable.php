<?php

namespace App\Mail;

use App\Models\FornecedorFatura;
use App\Models\Empresa;
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
		$empresa = Empresa::first();

		$assunto = 'Comprovativo de Pagamento - Fatura ' . $this->fatura->numero;

		$mail = $this->subject($assunto)
			->view('emails.comprovativo-fornecedor')
			->with([
				'fatura' => $this->fatura,
				'empresa' => $empresa,
			]);

		// Anexa comprovativo
		if ($this->fatura->comprovativo_path && Storage::disk('private')->exists($this->fatura->comprovativo_path)) {
			$mail->attach(Storage::disk('private')->path($this->fatura->comprovativo_path), [
				'as' => 'comprovativo.pdf',
				'mime' => 'application/pdf',
			]);
		}

		return $mail;
	}

	// SOBRESCREVE O MÉTODO DE ENVIO
	public function envelope()
	{
		return new \Illuminate\Mail\Mailables\Envelope(
			subject: $this->subject,
			to: [$this->fatura->fornecedor->email_enc],
		);
	}

	// FORÇA ENVIO IMEDIATO
	public function send($mailer)
	{
		$mailer->send($this);
	}
}
