<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;

class PurchaseOrderLine extends Model
{
	use HasFactory, BelongsToTenant;

	protected $table = 'encomenda_fornecedor_linhas';

	protected $fillable = [
		'encomenda_fornecedor_id',
		'artigo_id',
		'descricao',
		'qtd',
		'preco',
		'iva_id',
		'total_linha',
	];

	public function order()
	{
		return $this->belongsTo(PurchaseOrder::class, 'encomenda_fornecedor_id');
	}

	public function artigo()
	{
		return $this->belongsTo(Artigo::class, 'artigo_id');
	}
}
