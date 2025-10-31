<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrdemTrabalho extends Model
{
	use HasFactory;

	protected $table = 'ordens_trabalho';

	protected $fillable = [
		'numero',
		'cliente_id',
		'responsavel_id',
		'descricao',
		'data_inicio',
		'data_fim',
		'estado',
		'observacoes',
	];

	protected $casts = [
		'data_inicio' => 'date',
		'data_fim' => 'date',
	];

	public const ESTADOS = ['pendente', 'em_execucao', 'concluida', 'cancelada'];

	// Relacionamentos
	public function cliente()
	{
		return $this->belongsTo(Entidade::class, 'cliente_id');
	}

	public function responsavel()
	{
		return $this->belongsTo(User::class, 'responsavel_id');
	}

	// Helper para gerar número incremental do tipo OT0001
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
