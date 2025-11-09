<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EncomendaFornecedor extends Model
{
    use SoftDeletes;

    protected $table = 'encomenda_fornecedores';

    protected $fillable = [
        'numero',
        'data_encomenda',
        'fornecedor_id',
        'estado',
        'total',
        'encomenda_cliente_id'
    ];

    protected $casts = [
        'data_encomenda' => 'date',
        'total' => 'decimal:2',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Entidade::class, 'fornecedor_id');
    }

    public function encomendaCliente(): BelongsTo
    {
        return $this->belongsTo(EncomendaCliente::class, 'encomenda_cliente_id');
    }

    public function linhas(): HasMany
    {
        return $this->hasMany(EncomendaFornecedorLinha::class, 'encomenda_fornecedor_id');
    }
}