<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class SalesOrderLine extends Model
{
	use BelongsToTenant;
	protected $fillable = [
		'sales_order_id',
		'artigo_id',
		'descricao',
		'quantidade',
		'preco',
		'iva_id',
		'fornecedor_id',
		'total'
	];

	protected $casts = [
		'quantidade' => 'float',
		'preco' => 'float',
		'total' => 'float',
	];

	public function salesOrder(): BelongsTo
	{
		return $this->belongsTo(SalesOrder::class, 'sales_order_id');
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
