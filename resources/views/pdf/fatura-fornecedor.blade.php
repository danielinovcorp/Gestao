{{-- resources/views/pdf/fatura-fornecedor.blade.php --}}
<!DOCTYPE html>
<html lang="pt">

<head>
	<meta charset="UTF-8">
	<title>Fatura Fornecedor {{ $fatura->numero }}</title>
	<style>
		@page {
			margin: 20mm;
		}

		body {
			font-family: DejaVu Sans, sans-serif;
			font-size: 10pt;
			color: #333;
		}

		.header {
			text-align: center;
			margin-bottom: 30px;
		}

		.info {
			display: flex;
			justify-content: space-between;
			margin-bottom: 20px;
			font-size: 9pt;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 20px;
			font-size: 9pt;
		}

		th,
		td {
			border: 1px solid #e5e7eb;
			padding: 8px;
			text-align: left;
		}

		th {
			background-color: #f3f4f6;
		}

		.text-right {
			text-align: right;
		}

		.total {
			margin-top: 20px;
			font-size: 11pt;
			text-align: right;
		}

		.footer {
			margin-top: 40px;
			text-align: center;
			font-size: 8pt;
			color: #6b7280;
		}
	</style>
</head>

<body>

	<div class="header">
		<h1>Fatura Fornecedor</h1>
		<p><strong>Nº:</strong> {{ $fatura->numero }}</p>
		@if($fatura->encomendaFornecedor)
		<p><strong>Referente à Encomenda:</strong> {{ $fatura->encomendaFornecedor->numero }}</p>
		@endif
	</div>

	<div class="info">
		<div>
			<p><strong>Fornecedor:</strong> {{ $fatura->fornecedor->nome ?? '—' }}</p>
			@if($fatura->fornecedor->morada)
			<p>{{ $fatura->fornecedor->morada }}</p>
			@endif
			@if($fatura->fornecedor->codigo_postal && $fatura->fornecedor->localidade)
			<p>{{ $fatura->fornecedor->codigo_postal }} {{ $fatura->fornecedor->localidade }}</p>
			@endif
			@if($fatura->fornecedor->nif_enc)
			<p><strong>NIF:</strong> {{ $fatura->fornecedor->nif_enc }}</p>
			@endif
		</div>
		<div class="text-right">
			<p><strong>Data da Fatura:</strong> {{ $fatura->data_fatura->format('d/m/Y') }}</p>
			@if($fatura->data_vencimento)
			<p><strong>Vencimento:</strong> {{ $fatura->data_vencimento->format('d/m/Y') }}</p>
			@endif
			<p><strong>Estado:</strong> {{ $fatura->estado === 'paga' ? 'Paga' : 'Pendente' }}</p>
		</div>
	</div>

	<!-- Tabela simples (sem linhas detalhadas, apenas total) -->
	<table>
		<thead>
			<tr>
				<th>Descrição</th>
				<th class="text-right">Valor Total</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Fatura referente a serviços/produtos</td>
				<td class="text-right">{{ number_format($fatura->valor_total, 2) }} €</td>
			</tr>
		</tbody>
	</table>

	<div class="total">
		<p><strong>Total: {{ number_format($fatura->valor_total, 2) }} €</strong></p>
	</div>

	<div class="footer">
		<p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
	</div>

</body>

</html>