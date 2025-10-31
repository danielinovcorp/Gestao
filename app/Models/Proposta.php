<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposta extends Model
{
	protected $fillable = [
		'numero',
		'cliente_id',
		'data_proposta',
		'validade',
		'estado',
		'valor_total',
		'observacoes',
	];

	protected $casts = [
		'data_proposta' => 'date',
		'validade' => 'date',
		'observacoes' => 'array',
	];

	public function cliente(): BelongsTo
	{
		return $this->belongsTo(Entidade::class, 'cliente_id');
	}
	public function linhas(): HasMany
	{
		return $this->hasMany(PropostaLinha::class);
	}

	/** Recalcula cache do total (soma de subtotais) */
	public function recalcularTotal(): void
	{
		$this->valor_total = $this->linhas()->sum('subtotal') ?: 0;
		$this->save();
	}

	/** Fecha proposta: define data_proposta e validade (se nulos) e muda estado */
	public function fechar(): void
	{
		if ($this->estado === 'fechado') return;

		$hoje = Carbon::today();
		if (!$this->data_proposta) $this->data_proposta = $hoje;
		if (!$this->validade) $this->validade = $this->data_proposta->copy()->addDays(30);
		$this->estado = 'fechado';
		$this->save();
	}

	/** Accessor: está válida? (data atual <= validade) */
	public function isValida(): bool
	{
		return $this->validade ? Carbon::today()->lte($this->validade) : true;
	}

	/** Próximo número sequencial — pode trocar para uma tabela de sequences se preferir */
	public static function nextNumero(): int
	{
		$ultimo = static::max('numero');
		return $ultimo ? $ultimo + 1 : 1;
	}
}
