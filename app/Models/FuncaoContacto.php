<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class FuncaoContacto extends Model
{
	use BelongsToTenant;
	protected $table = 'funcoes_contacto';

	protected $fillable = ['nome', 'estado'];

	// (Opcional) relação inversa
	public function contactos()
	{
		return $this->hasMany(Contacto::class, 'funcao_id');
	}
}
