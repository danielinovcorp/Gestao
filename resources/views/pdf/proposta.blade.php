<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proposta #{{ $proposta->numero }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mt-4 { margin-top: 16px; }
        .text-lg { font-size: 14px; }
    </style>
</head>
<body>
    <h1>Proposta #{{ $proposta->numero }}</h1>

    <p><strong>Cliente:</strong> {{ $proposta->cliente->nome }}</p>
    <p><strong>Data da Proposta:</strong> {{ \Carbon\Carbon::parse($proposta->data_proposta)->format('d/m/Y') }}</p>
    <p><strong>Validade:</strong> {{ \Carbon\Carbon::parse($proposta->validade)->format('d/m/Y') }}</p>
    <p><strong>Estado:</strong> {{ $proposta->estado === 'fechado' ? 'Fechado' : 'Rascunho' }}</p>

    <table>
        <thead>
            <tr>
                <th>Ref</th>
                <th>Artigo</th>
                <th class="text-right">Qtd</th>
                <th class="text-right">Preço</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proposta->linhas as $linha)
                <tr>
                    <td>{{ $linha->artigo->referencia ?? '-' }}</td>
                    <td>{{ $linha->descricao }}</td>
                    <td class="text-right">{{ number_format($linha->qtd, 3) }}</td>
                    <td class="text-right">{{ number_format($linha->preco, 2) }} €</td>
                    <td class="text-right">{{ number_format($linha->total_linha, 2) }} €</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right font-bold text-lg">Total</td>
                <td class="text-right font-bold text-lg">{{ number_format($proposta->total, 2) }} €</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>