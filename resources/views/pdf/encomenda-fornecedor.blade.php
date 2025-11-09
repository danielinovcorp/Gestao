{{-- resources/views/pdf/encomenda-fornecedor.blade.php --}}
<!DOCTYPE html>
<html lang="pt">

<head>
	<meta charset="UTF-8">
	<title>Encomenda Fornecedor {{ $order->numero }}</title>
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
		<h1>Encomenda Fornecedor</h1>
		<p><strong>Nº:</strong> {{ $order->numero }}</p>
		<p><strong>De Encomenda Cliente:</strong> {{ $order->origem?->numero ? 'EC-' . $order->origem->numero : '—' }}</p>
	</div>

	<div class="info">
		<div>
			<p><strong>Fornecedor:</strong> {{ $order->fornecedor->nome ?? '—' }}</p>
			@if($order->fornecedor->morada)
			<p>{{ $order->fornecedor->morada }}</p>
			@endif
			@if($order->fornecedor->codigo_postal && $order->fornecedor->localidade)
			<p>{{ $order->fornecedor->codigo_postal }} {{ $order->fornecedor->localidade }}</p>
			@endif
			@if($order->fornecedor->nif_enc)
			<p><strong>NIF:</strong> {{ $order->fornecedor->nif_enc }}</p>
			@endif
		</div>
		<div class="text-right">
			<p><strong>Data:</strong> {{ $order->data_encomenda?->format('d/m/Y') ?? '—' }}</p>
			<p><strong>Estado:</strong> {{ ucfirst($order->estado) }}</p>
		</div>
	</div>

	<table>
		<thead>
			<tr>
				<th>Ref.</th>
				<th>Descrição</th>
				<th class="text-right">Qtd</th>
				<th class="text-right">Preço</th>
				<th class="text-right">Total</th>
			</tr>
		</thead>
		<tbody>
			@foreach($order->linhas as $linha)
			<tr>
				<td>{{ $linha->artigo?->referencia ?? '—' }}</td>
				<td>{{ $linha->descricao }}</td>
				<td class="text-right">{{ number_format($linha->quantidade, 3) }}</td>
				<td class="text-right">{{ number_format($linha->preco, 2) }} €</td>
				<td class="text-right">{{ number_format($linha->total, 2) }} €</td>
			</tr>
			@endforeach
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