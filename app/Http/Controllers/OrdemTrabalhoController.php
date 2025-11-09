<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdemTrabalhoRequest;
use App\Http\Requests\UpdateOrdemTrabalhoRequest;
use App\Models\OrdemTrabalho;
use App\Models\Entidade;
use App\Models\Artigo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrdemTrabalhoController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search', ''),
            'estado' => $request->get('estado', ''),
            'cliente_id' => $request->integer('cliente_id'),
            'prioridade' => $request->get('prioridade', ''),
        ];

        $query = OrdemTrabalho::query()
            ->with(['cliente:id,nome', 'servico:id,descricao,preco,nome'])
            ->when($filters['search'], function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('numero', 'like', "%{$search}%")
                       ->orWhere('descricao', 'like', "%{$search}%")
                       ->orWhere('observacoes', 'like', "%{$search}%")
                       ->orWhereHas('cliente', function ($qCliente) use ($search) {
                           $qCliente->where('nome', 'like', "%{$search}%");
                       })
                       ->orWhereHas('servico', function ($qServico) use ($search) {
                           $qServico->where('nome', 'like', "%{$search}%")
                                   ->orWhere('descricao', 'like', "%{$search}%");
                       });
                });
            })
            ->when($filters['estado'] && $filters['estado'] !== 'all', 
                fn($q, $estado) => $q->where('estado', $estado))
            ->when($filters['cliente_id'], 
                fn($q, $clienteId) => $q->where('cliente_id', $clienteId))
            ->when($filters['prioridade'] && $filters['prioridade'] !== 'all', 
                fn($q, $prioridade) => $q->where('prioridade', $prioridade))
            ->orderByRaw("FIELD(prioridade, 'urgente', 'alta', 'media', 'baixa')")
            ->orderByDesc('data_inicio')
            ->orderByDesc('id');

        $ordens = $query->paginate(15)->withQueryString();

        // Estatísticas
        $estatisticas = [
            'total' => OrdemTrabalho::count(),
            'pendentes' => OrdemTrabalho::where('estado', 'pendente')->count(),
            'em_execucao' => OrdemTrabalho::where('estado', 'em_execucao')->count(),
            'concluidas' => OrdemTrabalho::where('estado', 'concluida')->count(),
            'urgentes' => OrdemTrabalho::where('prioridade', 'urgente')->count(),
        ];

        $clientes = Entidade::query()
            ->select('id', 'nome')
            ->where(function ($q) {
                $q->where('tipo', 'cliente')->orWhere('tipo', 'both');
            })
            ->orderBy('nome')
            ->get();

        // Buscar TODOS os artigos ativos (produtos e serviços)
        $servicos = Artigo::query()
            ->select('id', 'nome', 'descricao', 'preco', 'referencia')
            ->where('estado', 'ativo')
            ->orderBy('nome')
            ->get();

        return Inertia::render('OrdensTrabalho/Index', [
            'ordens' => $ordens,
            'filters' => $filters,
            'clientes' => $clientes,
            'servicos' => $servicos,
            'estados' => OrdemTrabalho::ESTADOS,
            'prioridades' => OrdemTrabalho::PRIORIDADES,
            'estatisticas' => $estatisticas,
        ]);
    }

    public function store(StoreOrdemTrabalhoRequest $request)
    {
        $data = $request->validated();
        $data['numero'] = OrdemTrabalho::proximoNumero();
        
        $ordem = OrdemTrabalho::create($data);

        return redirect()
            ->route('ordens.index')
            ->with('success', "Ordem de Trabalho {$ordem->numero} criada com sucesso.");
    }

    public function update(UpdateOrdemTrabalhoRequest $request, OrdemTrabalho $ordem)
    {
        $ordem->update($request->validated());

        return back()
            ->with('success', "Ordem de Trabalho {$ordem->numero} atualizada com sucesso.");
    }

    public function destroy(OrdemTrabalho $ordem)
    {
        $numero = $ordem->numero;
        $ordem->delete();

        return back()
            ->with('success', "Ordem de Trabalho {$numero} removida com sucesso.");
    }
}
