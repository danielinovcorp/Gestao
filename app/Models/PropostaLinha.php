<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropostaLinha extends Model
{
	protected $fillable = [
		'proposta_id',
		'artigo_id',
		'fornecedor_id',
		'descricao',
		'referencia',
		'quantidade',
		'preco_unitario',
		'preco_custo',
		'subtotal',
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
}
