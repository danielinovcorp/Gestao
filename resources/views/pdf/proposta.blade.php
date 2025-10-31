<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<title>Proposta #{{ $proposta->numero }}</title>
	<style>
		body {
			font-family: DejaVu Sans, sans-serif;
			font-size: 12px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
		}

		th,
		td {
			border: 1px solid #ddd;
			padding: 6px;
		}

		th {
			background: #f2f2f2;
			text-align: left;
		}

		.right {
			text-align: right;
		}
	</style>
</head>

<body>
	<h2>Proposta #{{ $proposta->numero }}</h2>
	<p><strong>Cliente:</strong> {{ $proposta->cliente->nome }}</p>
	<p><strong>Data da Proposta:</strong> {{ optional($proposta->data_proposta)->format('d/m/Y') ?? '—' }}</p>
	<p><strong>Validade:</strong> {{ optional($proposta->validade)->format('d/m/Y') ?? '—' }}</p>
	<p><strong>Estado:</strong> {{ ucfirst($proposta->estado) }}</p>

	<table>
		<thead>
			<tr>
				<th>Ref</th>
				<th>Artigo</th>
				<th class="right">Qtd</th>
				<th class="right">Preço</th>
				<th class="right">Subtotal</th>
			</tr>
		</thead>
		<tbody>
			@foreach($proposta->linhas as $l)
			<tr>
				<td>{{ $l->referencia }}</td>
				<td>
					{{ $l->descricao }}
					@if($l->fornecedor)
					<small><br>Fornecedor: {{ $l->fornecedor->nome }}</small>
					@endif
				</td>
				<td class="right">{{ number_format($l->quantidade,3,',','.') }}</td>
				<td class="right">{{ number_format($l->preco_unitario,2,',','.') }} €</td>
				<td class="right">{{ number_format($l->subtotal,2,',','.') }} €</td>
			</tr>
			@endforeach
		</tbody>
		<tfoot>
			<tr>
				<th colspan="4" class="right">Total</th>
				<th class="right">{{ number_format($proposta->valor_total,2,',','.') }} €</th>
			</tr>
		</tfoot>
	</table>
</body>

</html>