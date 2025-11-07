<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contacto extends Model
{
	use SoftDeletes;

	protected $table = 'contactos';

	protected $fillable = [
		'numero',
		'entidade_id',
		'nome',
		'apelido',
		'funcao_id',

		// armazenadas cifradas
		'telefone_enc',
		'telemovel_enc',
		'email_enc',

		// se existir coluna simples, ok manter
		'telemovel',

		'observacoes',
		'consentimento_rgpd',
		'estado',
	];

	protected $casts = [
		'numero' => 'integer',
		'entidade_id' => 'integer',
		'funcao_id' => 'integer',
		'deleted_at' => 'datetime',
	];

	public function entidade()
	{
		return $this->belongsTo(Entidade::class, 'entidade_id');
	}

	public function funcao()
	{
		return $this->belongsTo(FuncaoContacto::class, 'funcao_id');
	}

	// Acessors “virtuais” para ler já decriptado
	public function getTelefoneAttribute(): ?string
	{
		return $this->telefone_enc ? decrypt($this->telefone_enc) : null;
	}
	public function getEmailAttribute(): ?string
	{
		return $this->email_enc ? decrypt($this->email_enc) : null;
	}
	public function getTelemovelDecAttribute(): ?string
	{
		return $this->telemovel_enc ? decrypt($this->telemovel_enc) : null;
	}
}
