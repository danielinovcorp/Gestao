<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FornecedorFatura;

class FornecedorFaturaPolicy
{
	public function viewAny(User $user): bool
	{
		return $user->can('ver faturas fornecedor') || $user->hasRole('admin');
	}

	public function view(User $user, FornecedorFatura $model): bool
	{
		return $this->viewAny($user);
	}

	public function create(User $user): bool
	{
		return $user->can('criar faturas fornecedor') || $user->hasRole('admin');
	}

	public function update(User $user, FornecedorFatura $model): bool
	{
		return $user->can('editar faturas fornecedor') || $user->hasRole('admin');
	}

	public function delete(User $user, FornecedorFatura $model): bool
	{
		return $user->can('remover faturas fornecedor') || $user->hasRole('admin');
	}
}
