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
		return $u->can('criar documentos');
	}
	public function update(User $u, Doc $doc): bool
	{
		return $u->can('editar documentos');
	}
	public function delete(User $u, Doc $doc): bool
	{
		return $u->can('remover documentos');
	}
	public function download(User $u, Doc $doc): bool
	{
		return $u->can('download documentos') || $this->view($u, $doc);
	}
}
