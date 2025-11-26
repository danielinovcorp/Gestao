<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class ContaBancaria extends Model
{
	use SoftDeletes, BelongsToTenant;

	protected $table = 'conta_bancarias';

	protected $fillable = [
		'banco',
		'titular',
		'iban',
		'swift_bic',
		'numero_conta',
		'saldo_abertura',
		'ativo',
		'notas'
	];

	protected $casts = [
		'saldo_abertura' => 'decimal:2',
		'ativo' => 'boolean',
	];
}
