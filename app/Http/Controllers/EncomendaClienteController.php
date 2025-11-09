<?php

namespace App\Http\Controllers;

use App\Models\EncomendaCliente;
use App\Models\EncomendaFornecedor;
use App\Models\Entidade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class EncomendaClienteController extends Controller
{
    public function index()
    {
        $encomendas = EncomendaCliente::with('cliente')
            ->select('id', 'numero', 'data_encomenda as data', 'cliente_id', 'estado', 'total')
            ->addSelect(['validade' => DB::raw('NULL')]) // se não tiver, simula
            ->paginate(15);

        return Inertia::render('Encomendas/Clientes/Index', [
            'orders' => $encomendas
        ]);
    }

    public function create()
    {
        return Inertia::render('Encomendas/Clientes/Form', [
            'clientes' => Entidade::where('is_cliente', 1)->select('id', 'nome')->get(),
            'fornecedores' => Entidade::where('is_fornecedor', 1)->select('id', 'nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => 'required|exists:entidades,id',
            'validade' => 'nullable|date',
            'estado' => 'required|in:rascunho,fechado',
            'linhas' => 'required|array|min:1',
            'linhas.*.artigo_id' => 'nullable|exists:artigos,id',
            'linhas.*.descricao' => 'required|string',
            'linhas.*.qtd' => 'required|numeric|min:0.001',
            'linhas.*.preco' => 'required|numeric|min:0',
            'linhas.*.fornecedor_id' => 'nullable|exists:entidades,id',
            'linhas.*.iva_id' => 'nullable|exists:ivas,id',
        ]);

        $encomenda = EncomendaCliente::create([
            'cliente_id' => $data['cliente_id'],
            'data_encomenda' => $data['estado'] === 'fechado' ? now()->format('Y-m-d') : null,
            'estado' => $data['estado'],
            'numero' => $data['estado'] === 'fechado' ? EncomendaCliente::gerarNumero() : null,
        ]);

        foreach ($data['linhas'] as $l) {
            $encomenda->linhas()->create([
                'artigo_id' => $l['artigo_id'] ?? null,
                'descricao' => $l['descricao'],
                'qtd' => $l['qtd'],
                'preco' => $l['preco'],
                'fornecedor_id' => $l['fornecedor_id'] ?? null,
                'iva_id' => $l['iva_id'] ?? null,
            ]);
        }

        $encomenda->calcularTotal();

        return redirect()->route('encomendas.clientes.index');
    }

    public function fechar($id)
    {
        $e = EncomendaCliente::findOrFail($id);
        $e->update([
            'estado' => 'fechado',
            'data_encomenda' => now()->format('Y-m-d'),
            'numero' => $e->numero ?? EncomendaCliente::gerarNumero(),
        ]);
        $e->calcularTotal();
        return back();
    }

    public function converter($id)
    {
        $ec = EncomendaCliente::with('linhas.fornecedor')->findOrFail($id);

        if ($ec->estado !== 'fechado') {
            return back()->withErrors(['error' => 'Encomenda deve estar fechada.']);
        }

        $porFornecedor = $ec->linhas->groupBy('fornecedor_id');

        foreach ($porFornecedor as $fid => $linhas) {
            if (!$fid) continue;

            $ef = EncomendaFornecedor::create([
                'fornecedor_id' => $fid,
                'data_encomenda' => now()->format('Y-m-d'),
                'estado' => 'rascunho',
                'numero' => null,
            ]);

            foreach ($linhas as $l) {
                $ef->linhas()->create([
                    'artigo_id' => $l->artigo_id,
                    'descricao' => $l->descricao,
                    'qtd' => $l->qtd,
                    'preco' => $l->preco,
                    'iva_id' => $l->iva_id,
                ]);
            }

            $ef->update([
                'total' => $ef->linhas->sum('total_linha'),
                'numero' => EncomendaFornecedor::gerarNumero(),
            ]);
        }

        return redirect()->route('encomendas.fornecedores.index');
    }
}