<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Concerns\BelongsToTenant;

class FornecedorFatura extends Model
{
	use HasFactory, SoftDeletes, LogsActivity, BelongsToTenant;

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


	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['numero', 'estado', 'valor_total', 'data_fatura', 'fornecedor_id'])
			->logOnlyDirty()
			->dontSubmitEmptyLogs()
			->setDescriptionForEvent(fn(string $eventName) => "Fatura {$this->numero} {$eventName}")
			->useLogName('faturas-fornecedor');
	}
}
