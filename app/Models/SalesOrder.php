<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesOrder extends Model
{
	use HasFactory;

	protected $fillable = [
		'numero',
		'cliente_id',
		'estado',
		'data_proposta',
		'validade',
		'total',
	];

	protected $casts = [
		'data_proposta' => 'datetime',
		'validade'      => 'date',
	];

	public function cliente()
	{
		return $this->belongsTo(Entidade::class, 'cliente_id');
	}

	public function linhas()
	{
		return $this->hasMany(SalesOrderLine::class);
	}

	public function fornecedorOrders()
	{
		// encomendas de fornecedor geradas a partir desta
		return $this->hasMany(PurchaseOrder::class, 'sales_order_id');
	}
}
