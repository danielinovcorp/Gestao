<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{
	use HasFactory;

	// 🟢 Corrige para o nome real da tabela
	protected $table = 'encomendas_fornecedores';

	protected $fillable = [
		'numero',
		'fornecedor_id',
		'sales_order_id',
		'estado',
		'total',
	];

	public function fornecedor()
	{
		return $this->belongsTo(Entidade::class, 'fornecedor_id');
	}

	public function origem()
	{
		// Encomenda de cliente que deu origem a esta
		return $this->belongsTo(SalesOrder::class, 'sales_order_id');
	}

	public function linhas()
	{
		return $this->hasMany(PurchaseOrderLine::class, 'encomenda_fornecedor_id');
	}
}
