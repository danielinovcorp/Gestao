<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
	/** @use HasFactory<\Database\Factories\UserFactory> */
	use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

	// Deixa sem tipagem para o Spatie ler corretamente
	protected $guard_name = 'web';

	/**
	 * Atributos atribuíveis em massa.
	 * Inclui phone e status para o CRUD de Utilizadores.
	 */
	protected $fillable = [
		'name',
		'email',
		'password',
		'phone',
		'status', // 'active' | 'inactive'
	];

	/**
	 * Atributos escondidos na serialização.
	 */
	protected $hidden = [
		'password',
		'remember_token',
		'two_factor_secret',
		'two_factor_recovery_codes',
	];

	/**
	 * Casts.
	 */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
			'two_factor_confirmed_at' => 'datetime',
			// Se quiseres, força enum simples:
			// 'status' => 'string',
		];
	}

	/**
	 * Helpers opcionais para a UI.
	 */
	public function isActive(): bool
	{
		return $this->status === 'active';
	}
}
