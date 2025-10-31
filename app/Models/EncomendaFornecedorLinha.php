<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncomendaFornecedorLinha extends Model
{
	protected $table = 'encomendas_fornecedores_linhas';

	protected $fillable = [
		'encomenda_fornecedor_id',
		// ... os campos de linha (artigo_id, qtd, preço, etc.)
	];

	public function encomenda()
	{
		return $this->belongsTo(EncomendaFornecedor::class, 'encomenda_fornecedor_id');
	}
}
