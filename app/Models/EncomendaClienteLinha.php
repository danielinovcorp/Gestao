<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncomendaClienteLinha extends Model
{
    protected $table = 'encomenda_cliente_linhas';

    protected $fillable = [
        'encomenda_id',
        'artigo_id',
        'descricao',
        'qtd',
        'preco',
        'fornecedor_id',
        'iva_id',
        'total_linha'
    ];

    protected $casts = [
        'qtd' => 'decimal:3',
        'preco' => 'decimal:2',
        'total_linha' => 'decimal:2',
    ];

    public function encomenda(): BelongsTo
    {
        return $this->belongsTo(EncomendaCliente::class, 'encomenda_id');
    }

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(Artigo::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Entidade::class, 'fornecedor_id');
    }
}