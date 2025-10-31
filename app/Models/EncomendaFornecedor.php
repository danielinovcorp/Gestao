<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EncomendaFornecedor extends Model
{
	use SoftDeletes;

	// Tabela real no BD
	protected $table = 'encomendas_fornecedores';

	protected $fillable = [
		'numero',
		'fornecedor_id',
		// ... outros campos que tiveres nessa tabela
	];

	public function fornecedor()
	{
		return $this->belongsTo(Entidade::class, 'fornecedor_id');
	}

	public function linhas()
	{
		// Tabela de linhas: encomendas_fornecedores_linhas
		return $this->hasMany(EncomendaFornecedorLinha::class, 'encomenda_fornecedor_id');
	}
}
