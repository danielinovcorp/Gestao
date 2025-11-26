<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class EncomendaFornecedorLinha extends Model
{
	use BelongsToTenant;
    protected $table = 'encomenda_fornecedores_linhas';

    protected $fillable = [
        'encomenda_fornecedor_id',
        'artigo_id',
        'descricao',
        'qtd',
        'preco',
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
        return $this->belongsTo(EncomendaFornecedor::class, 'encomenda_fornecedor_id');
    }

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(Artigo::class);
    }
}
