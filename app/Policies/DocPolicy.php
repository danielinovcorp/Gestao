<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Doc;

class DocPolicy
{
	public function viewAny(User $u): bool
	{
		return $u->can('ver documentos');
	}
	public function view(User $u, Doc $doc): bool
	{
		return $u->can('ver documentos');
	}
	public function create(User $u): bool
	{
		return false; // ninguém faz upload manual aqui
	}

	public function update(User $u, Doc $doc): bool
	{
		return false; // não edita
	}

	public function delete(User $u, Doc $doc): bool
	{
		return $u->can('remover documentos'); // só admin
	}
	public function download(User $u, Doc $doc): bool
	{
		return $u->can('download documentos') || $this->view($u, $doc);
	}
}
