<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Empresa extends Model
{
	use HasFactory;

	protected $table = 'empresa';

	protected $fillable = [
		'nome',
		'morada',
		'codigo_postal',
		'localidade',
		'nif',
		'logo_path',
	];

	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];
}
