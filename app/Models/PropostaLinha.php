<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class PropostaLinha extends Model
{
	use BelongsToTenant;
    protected $fillable = [
        'proposta_id',
        'artigo_id',
        'fornecedor_id',
        'descricao',
        'qtd',           // ✅ CORRETO: 'qtd' em vez de 'quantidade'
        'preco',         // ✅ CORRETO: 'preco' em vez de 'preco_unitario'
        'preco_custo',
        'total_linha',   // ✅ CORRETO: 'total_linha' em vez de 'subtotal'
        'iva_id',        // ✅ ADICIONADO: campo que existe na tabela
    ];

    protected $casts = [
        'qtd' => 'decimal:3',
        'preco' => 'decimal:2',
        'preco_custo' => 'decimal:2',
        'total_linha' => 'decimal:2',
    ];

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }
    
    public function artigo(): BelongsTo
    {
        return $this->belongsTo(Artigo::class);
    }
    
    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Entidade::class, 'fornecedor_id');
    }

    /** Calcula o total_linha automaticamente */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($linha) {
            $linha->total_linha = $linha->qtd * $linha->preco;
        });
    }
}