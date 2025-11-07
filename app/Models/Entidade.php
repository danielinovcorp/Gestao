<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entidade extends Model
{
	use SoftDeletes, HasFactory;

	protected $table = 'entidades';

	protected $fillable = [
		'numero',
		'is_cliente',
		'is_fornecedor',
		'nif_enc',
		'nif_hash',
		'nome',
		'morada',
		'codigo_postal',
		'localidade',
		'pais_id',
		'telefone_enc',
		'telemovel_enc',
		'website',
		'email_enc',
		'consentimento_rgpd',
		'observacoes',
		'estado',
	];

	protected $casts = [
		'nif_enc'      => 'encrypted',
		'telefone_enc' => 'encrypted',
		'telemovel_enc' => 'encrypted',
		'email_enc'    => 'encrypted',
		'is_cliente'   => 'boolean',
		'is_fornecedor' => 'boolean',
	];

	public function scopeClientes($q)
	{
		return $q->where('is_cliente', true);
	}
	public function scopeFornecedores($q)
	{
		return $q->where('is_fornecedor', true);
	}

	public function scopeSearch($q, ?string $s)
	{
		if (!$s) return $q;
		$s = trim($s);
		return $q->where(function ($qq) use ($s) {
			$qq->where('nome', 'like', "%{$s}%")
				->orWhere('nif_hash', hash('sha256', static::normalizeNif($s)));
		});
	}

	public static function normalizeNif(string $nif): string
	{
		return strtoupper(preg_replace('/\D+/', '', $nif)); // só dígitos
	}
}
