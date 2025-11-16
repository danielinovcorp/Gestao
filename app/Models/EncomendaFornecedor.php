<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EncomendaFornecedor extends Model
{
	use SoftDeletes, LogsActivity;

	protected $table = 'encomendas_fornecedores'; // Nome correto no plural

	protected $fillable = [
		'numero',
		'data_encomenda',
		'fornecedor_id',
		'estado',
		'total',
		'encomenda_cliente_id'
	];

	protected $casts = [
		'data_encomenda' => 'date',
		'total' => 'decimal:2',
	];

	public function fornecedor(): BelongsTo
	{
		return $this->belongsTo(Entidade::class, 'fornecedor_id');
	}

	public function encomendaCliente(): BelongsTo
	{
		return $this->belongsTo(EncomendaCliente::class, 'encomenda_cliente_id');
	}

	public function linhas(): HasMany
	{
		return $this->hasMany(EncomendaFornecedorLinha::class, 'encomenda_fornecedor_id');
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['numero', 'estado', 'data_encomenda', 'fornecedor_id'])
			->logOnlyDirty()
			->setDescriptionForEvent(fn(string $eventName) => "Encomenda {$this->numero} {$eventName}")
			->useLogName('encomendas-fornecedor');
	}
}
