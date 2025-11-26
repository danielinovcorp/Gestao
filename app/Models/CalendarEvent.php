<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class CalendarEvent extends Model
{
	use HasFactory, SoftDeletes, BelongsToTenant;

	protected $fillable = [
		'created_by',
		'user_id',
		'entidade_id',
		'calendar_type_id',
		'calendar_action_id',
		'start',
		'end',
		'duration_minutes',
		'descricao',
		'estado',
		'partilha_global',
		'conhecimento_global',
	];

	protected $casts = [
		'start' => 'datetime',
		'end' => 'datetime',
		'partilha_global' => 'boolean',
		'conhecimento_global' => 'boolean',
	];

	public function responsavel()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
	public function criador()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
	public function entidade()
	{
		return $this->belongsTo(Entidade::class);
	}
	public function tipo()
	{
		return $this->belongsTo(CalendarType::class, 'calendar_type_id');
	}
	public function acao()
	{
		return $this->belongsTo(CalendarAction::class, 'calendar_action_id');
	}

	public function partilhas()
	{
		return $this->belongsToMany(User::class, 'calendar_event_shares')->withTimestamps();
	}
	public function conhecimentos()
	{
		return $this->belongsToMany(User::class, 'calendar_event_ccs')->withTimestamps();
	}

	public function titulo(): string
	{
		$pfx = $this->tipo?->nome ? ("[{$this->tipo->nome}]") : '';
		$who = $this->entidade?->nome ?: ($this->responsavel?->name ?: '');
		return trim("$pfx $who");
	}
}
