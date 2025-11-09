<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{
	use HasFactory;

	protected $table = 'encomendas_fornecedores';

	protected $casts = [
		'data_encomenda' => 'date',
		'created_at'     => 'datetime',
		'updated_at'     => 'datetime',
		'deleted_at'     => 'datetime',
	];

	protected $fillable = [
		'numero',
		'fornecedor_id',
		'origem_id',        // ← CORRIGIDO!
		'estado',
		'total',
		'data_encomenda',
	];

	public function fornecedor()
	{
		return $this->belongsTo(Entidade::class, 'fornecedor_id');
	}

	public function origem()
	{
		return $this->belongsTo(SalesOrder::class, 'origem_id'); // ← CORRIGIDO!
	}

	public function linhas()
	{
		return $this->hasMany(PurchaseOrderLine::class, 'encomenda_fornecedor_id');
	}
}
