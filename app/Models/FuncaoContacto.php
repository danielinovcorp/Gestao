<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuncaoContacto extends Model
{
	protected $table = 'funcoes_contacto';

	protected $fillable = ['nome', 'estado'];

	// (Opcional) relação inversa
	public function contactos()
	{
		return $this->hasMany(Contacto::class, 'funcao_id');
	}
}
