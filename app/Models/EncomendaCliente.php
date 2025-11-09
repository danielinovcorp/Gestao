<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EncomendaCliente extends Model
{
    use SoftDeletes;

    protected $table = 'encomenda_clientes';

    protected $fillable = [
        'numero',
        'data_encomenda',
        'cliente_id',
        'estado',
        'total',
        'validade',
        'proposta_id'
    ];

    protected $casts = [
        'data_encomenda' => 'date',
        'total' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Entidade::class, 'cliente_id');
    }

    public function linhas(): HasMany
    {
        return $this->hasMany(EncomendaClienteLinha::class, 'encomenda_id');
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }
}