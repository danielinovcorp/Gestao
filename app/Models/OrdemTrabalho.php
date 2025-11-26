<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Concerns\BelongsToTenant;

class OrdemTrabalho extends Model
{
	use HasFactory, LogsActivity, BelongsToTenant;

	protected $table = 'ordens_trabalho';

	protected $fillable = [
		'numero',
		'cliente_id',
		'servico_id', // Vem da tabela artigos (serviços)
		'descricao',
		'data_inicio',
		'data_fim',
		'estado',
		'observacoes',
		'prioridade', // baixa, media, alta, urgente
	];

	protected $casts = [
		'data_inicio' => 'date',
		'data_fim' => 'date',
	];

	public const ESTADOS = [
		'pendente' => 'Pendente',
		'agendada' => 'Agendada',
		'em_execucao' => 'Em Execução',
		'concluida' => 'Concluída',
		'cancelada' => 'Cancelada'
	];

	public const PRIORIDADES = [
		'baixa' => 'Baixa',
		'media' => 'Média',
		'alta' => 'Alta',
		'urgente' => 'Urgente'
	];

	// Activity Log
	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['numero', 'estado', 'data_inicio', 'data_fim', 'prioridade'])
			->logOnlyDirty()
			->setDescriptionForEvent(fn(string $eventName) => "OT {$this->numero} {$eventName}")
			->dontSubmitEmptyLogs();
	}

	// Relacionamentos
	public function cliente()
	{
		return $this->belongsTo(Entidade::class, 'cliente_id');
	}

	public function servico()
	{
		return $this->belongsTo(Artigo::class, 'servico_id');
	}

	// Helper para número incremental
	public static function proximoNumero(): string
	{
		$ultimo = static::orderByDesc('id')->value('numero');
		if (!$ultimo || !preg_match('/^OT(\d{4,})$/', $ultimo, $m)) {
			return 'OT0001';
		}
		$n = (int)$m[1] + 1;
		return 'OT' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
	}
}
