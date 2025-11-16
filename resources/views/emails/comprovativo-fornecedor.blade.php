<!DOCTYPE html>
<html lang="pt">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Comprovativo de Pagamento</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			color: #333;
			line-height: 1.6;
			margin: 0;
			padding: 0;
			background-color: #f9f9f9;
		}

		.container {
			max-width: 600px;
			margin: 20px auto;
			background: #fff;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		}

		.logo {
			text-align: center;
			margin-bottom: 30px;
		}

		.logo img {
			max-height: 80px;
			width: auto;
		}

		.content {
			margin-bottom: 30px;
		}

		.footer {
			font-size: 12px;
			color: #777;
			text-align: center;
			margin-top: 40px;
			border-top: 1px solid #eee;
			padding-top: 15px;
		}
	</style>
</head>

<body>
	<div class="container">
		<!-- LOGO -->
		<div class="logo">
			@if($empresa?->logo_path && file_exists(public_path('storage/' . $empresa->logo_path)))
			<img src="{{ asset('storage/' . $empresa->logo_path) }}" alt="{{ $empresa->nome ?? 'Empresa' }}">
			@else
			<h2 style="margin: 0; color: #1f2937;">{{ $empresa->nome ?? 'Sistema Gestão' }}</h2>
			@endif
		</div>

		<!-- MENSAGEM -->
		<div class="content">
			<p>Estimado(a) <strong>{{ $fatura->fornecedor->nome }}</strong>,</p>

			<p>Enviamos em anexo o comprovativo de pagamento da fatura <strong>{{ $fatura->numero }}</strong>.</p>

			<p>Qualquer questão, entre em contacto connosco.</p>

			<p>Cumprimentos,<br>
				<strong>{{ $empresa->nome ?? 'Sistema Gestão' }}</strong>
			</p>
		</div>

		<!-- RODAPÉ -->
		<div class="footer">
			<p>Este é um email automático. Não responder.</p>
		</div>
	</div>
</body>

</html>