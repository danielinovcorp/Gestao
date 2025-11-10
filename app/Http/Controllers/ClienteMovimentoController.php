<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteMovimentoRequest;
use App\Models\ClienteMovimento;
use App\Models\Entidade;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteMovimentoController extends Controller
{
    public function index(Request $request)
    {
        $clienteId = $request->integer('cliente');

        $movs = ClienteMovimento::query()
            ->with('cliente:id,nome')
            ->when($clienteId, fn($q) => $q->where('cliente_id', $clienteId))
            ->orderByDesc('data')->orderByDesc('id')
            ->paginate(20);

        // saldo corrente (sum crédito - débito) do filtro
        $saldo = ClienteMovimento::query()
            ->when($clienteId, fn($q) => $q->where('cliente_id', $clienteId))
            ->selectRaw('COALESCE(SUM(credito - debito),0) as saldo')
            ->value('saldo');

        return Inertia::render('Financeiro/ContaCorrenteClientes/Index', [
            'filters' => $request->only(['cliente']),
            'movimentos' => $movs->through(fn($m) => [
                'id' => $m->id,
                'data' => $m->data->format('d/m/Y'),
                'data_iso' => $m->data->format('Y-m-d'),
                'cliente_id' => $m->cliente_id,
                'cliente' => $m->cliente?->nome,
                'descricao' => $m->descricao,
                'documento_tipo' => $m->documento_tipo,
                'documento_numero' => $m->documento_numero,
                'doc' => trim(($m->documento_tipo ?? '') . ' ' . ($m->documento_numero ?? '')),
                'debito' => number_format($m->debito, 2, ',', '.'),
                'debito_raw' => (float) $m->debito,
                'credito' => number_format($m->credito, 2, ',', '.'),
                'credito_raw' => (float) $m->credito,
            ]),
            'clientes' => Entidade::where(fn($q) => $q->where('tipo', 'cliente')->orWhere('tipo', 'both'))
                ->orderBy('nome')->get(['id', 'nome']),
            'saldo' => number_format($saldo, 2, ',', '.'),
            'saldo_raw' => (float) $saldo, // Garantir que seja número
        ]);
    }

    public function store(StoreClienteMovimentoRequest $request)
    {
        ClienteMovimento::create($request->validated());
        return back()->with('success', 'Lançamento inserido com sucesso.');
    }

    public function destroy(ClienteMovimento $clienteMovimento)
    {
        $clienteMovimento->delete();
        return back()->with('success', 'Lançamento removido com sucesso.');
    }
}