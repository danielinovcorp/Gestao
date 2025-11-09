{{-- resources/views/pdf/encomenda-cliente.blade.php --}}
<!DOCTYPE html>
<html lang="pt">

<head>
	<meta charset="UTF-8">
	<title>Encomenda EC-{{ $order->numero }}</title>
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

		.header h1 {
			margin: 0;
			font-size: 18pt;
			color: #1f2937;
		}

		.info {
			display: flex;
			justify-content: space-between;
			margin-bottom: 20px;
			font-size: 9pt;
		}

		.info div {
			flex: 1;
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
			font-weight: 600;
		}

		.text-right {
			text-align: right;
		}

		.total {
			margin-top: 20px;
			font-size: 11pt;
			text-align: right;
		}

		.total strong {
			font-size: 12pt;
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
		<h1>Encomenda Cliente</h1>
		<p><strong>Nº:</strong> EC-{{ date('Y') }}-{{ str_pad($order->numero, 4, '0', STR_PAD_LEFT) }}</p>
	</div>

	<div class="info">
		<div>
			<p><strong>Cliente:</strong> {{ $order->cliente->nome ?? '—' }}</p>
			@if($order->cliente->morada)
			<p><strong>Morada:</strong> {{ $order->cliente->morada }}</p>
			@endif
			@if($order->cliente->codigo_postal && $order->cliente->localidade)
			<p><strong>Código-Postal:</strong> {{ $order->cliente->codigo_postal }}</p>
			<p><strong>Localidade:</strong> {{ $order->cliente->localidade }}</p>
			@endif
			@if($order->cliente->nif)
			<p><strong>NIF:</strong> {{ $order->cliente->nif }}</p>
			@endif
		</div>
		<div class="text-right">
			<p><strong>Data:</strong> {{ $order->data_proposta?->format('d/m/Y') ?? '—' }}</p>
			@if($order->validade)
			<p><strong>Validade:</strong> {{ $order->validade->format('d/m/Y') }}</p>
			@endif
			<p><strong>Estado:</strong> {{ ucfirst($order->estado) }}</p>
		</div>
	</div>

	<table>
		<thead>
			<tr>
				<th>Ref.</th>
				<th>Descrição</th>
				<th class="text-right">Qtd</th>
				<th class="text-right">Preço Unit.</th>
				<th class="text-right">Total</th>
			</tr>
		</thead>
		<tbody>
			@forelse($order->linhas as $linha)
			<tr>
				<td>{{ $linha->artigo?->referencia ?? '—' }}</td>
				<td>{{ $linha->descricao ?? $linha->artigo?->nome ?? 'Sem descrição' }}</td>
				<td class="text-right">{{ number_format($linha->quantidade, 3) }}</td>
				<td class="text-right">{{ number_format($linha->preco, 2) }} €</td>
				<td class="text-right">{{ number_format($linha->total, 2) }} €</td>
			</tr>
			@empty
			<tr>
				<td colspan="5" class="text-center text-slate-500">Sem linhas</td>
			</tr>
			@endforelse
		</tbody>
	</table>

	<div class="total">
		<p><strong>Total: {{ number_format($order->total, 2) }} €</strong></p>
	</div>

	<div class="footer">
		<p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
	</div>

</body>

</html>