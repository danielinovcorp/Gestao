<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FornecedorFatura extends Model
{
	use SoftDeletes;

	protected $fillable = [
		'numero',
		'data_fatura',
		'data_vencimento',
		'fornecedor_id',
		'encomenda_fornecedor_id',
		'valor_total',
		'documento_path',
		'comprovativo_path',
		'estado',
		'comprovativo_enviado_em',
	];

	protected $casts = [
		'data_fatura' => 'date',
		'data_vencimento' => 'date',
		'valor_total' => 'decimal:2',
		'comprovativo_enviado_em' => 'datetime',
	];

	public function fornecedor()
	{
		return $this->belongsTo(Entidade::class, 'fornecedor_id');
	}

	public function encomendaFornecedor()
	{
		return $this->belongsTo(EncomendaFornecedor::class, 'encomenda_fornecedor_id');
	}

	public function getDocumentoUrlAttribute(): ?string
	{
		return $this->documento_path
			? route('files.private.show', ['path' => $this->documento_path])
			: null;
	}

	public function getComprovativoUrlAttribute(): ?string
	{
		return $this->comprovativo_path
			? route('files.private.show', ['path' => $this->comprovativo_path])
			: null;
	}
}
