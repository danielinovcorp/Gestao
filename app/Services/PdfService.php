<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
	public function render(string $view, array $data = []): \Barryvdh\DomPDF\PDF
	{
		return Pdf::loadView($view, $data)->setPaper('a4');
	}
}
