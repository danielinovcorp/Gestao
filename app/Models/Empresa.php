<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;

class Empresa extends Model
{
	use HasFactory, BelongsToTenant;

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
