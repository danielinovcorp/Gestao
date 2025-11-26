<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToTenant;

class SalesOrder extends Model
{
	use BelongsToTenant;
	/**
	 * Nome da tabela no banco
	 */
	protected $table = 'sales_orders';

	/**
	 * Campos que podem ser preenchidos em massa
	 */
	protected $fillable = [
		'numero',
		'data_proposta',
		'cliente_id',
		'validade',
		'total',
		'estado',
		'proposta_id', // ← ADICIONADO!
	];

	/**
	 * Casts para tipos corretos
	 */
	protected $casts = [
		'data_proposta' => 'datetime',
		'validade'      => 'date',
		'total'         => 'float',
		'proposta_id'   => 'integer',
	];

	/**
	 * Relacionamento com Cliente
	 */
	public function cliente(): BelongsTo
	{
		return $this->belongsTo(Entidade::class, 'cliente_id');
	}

	/**
	 * Relacionamento com Linhas da Encomenda
	 */
	public function linhas(): HasMany
	{
		return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
	}

	/**
	 * Relacionamento com Proposta (origem)
	 */
	public function proposta(): BelongsTo
	{
		return $this->belongsTo(Proposta::class, 'proposta_id');
	}

	/**
	 * (Opcional) Relacionamento reverso com Encomendas Fornecedor
	 */
	public function encomendasFornecedor(): HasMany
	{
		return $this->hasMany(PurchaseOrder::class, 'origem_id');
	}
}
