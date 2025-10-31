<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
	protected $table = 'contactos';

	protected $fillable = [
		'numero',
		'entidade_id',
		'nome',
		'apelido',
		'funcao_id',
		'telefone',
		'telemovel',
		'email',
		'cargo',         // se já usas
		'observacoes',
		'consentimento_rgpd',
		'estado',
		'principal',     // se já usas
	];

	protected $casts = [
		'numero' => 'integer',
		'entidade_id' => 'integer',
		'funcao_id' => 'integer',
		'principal' => 'boolean',
	];

	public function entidade()
	{
		return $this->belongsTo(Entidade::class, 'entidade_id');
	}

	public function funcao()
	{
		return $this->belongsTo(FuncaoContacto::class, 'funcao_id');
	}
}
