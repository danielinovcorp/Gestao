<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesOrderLine extends Model
{
	use HasFactory;

	protected $fillable = [
		'sales_order_id',
		'artigo_id',
		'descricao',
		'quantidade',
		'preco',
		'iva_id',
		'fornecedor_id',
		'total',
	];

	public function order()
	{
		return $this->belongsTo(SalesOrder::class, 'sales_order_id');
	}

	public function artigo()
	{
		return $this->belongsTo(Artigo::class);
	}

	public function fornecedor()
	{
		return $this->belongsTo(Entidade::class, 'fornecedor_id');
	}
}
