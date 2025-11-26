<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToTenant;

class Proposta extends Model
{
	use BelongsToTenant;
	protected $fillable = [
		'numero',
		'cliente_id',
		'data_proposta',
		'validade',
		'estado',
		'total',          // ✅ CORRETO: 'total' em vez de 'valor_total'
		'observacoes',
	];

	protected $casts = [
		'data_proposta' => 'date',
		'validade' => 'date',
		'observacoes' => 'array',
		'total' => 'decimal:2', // ✅ CORRETO
	];

	public function cliente(): BelongsTo
	{
		return $this->belongsTo(Entidade::class, 'cliente_id');
	}

	public function linhas(): HasMany
	{
		return $this->hasMany(PropostaLinha::class);
	}

	/** Recalcula cache do total (soma de total_linha) */
	public function recalcularTotal(): void
	{
		$this->total = $this->linhas()->sum('total_linha') ?: 0; // ✅ CORRETO: 'total_linha'
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

	/** Próximo número sequencial */
	public static function nextNumero(): int
	{
		$ultimo = static::max('numero');
		return $ultimo ? $ultimo + 1 : 1;
	}
}
