<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
	public function __construct()
	{
		$this->middleware(['auth', 'active']);
		// ✅ trocado para can:
		$this->middleware('can:access.roles.view')->only(['index']);
		$this->middleware('can:access.roles.create')->only(['store']);
		$this->middleware('can:access.roles.update')->only(['update', 'toggleStatus']);
		$this->middleware('can:access.roles.delete')->only(['destroy']);
	}

	public function index(Request $request)
	{
		$search = $request->string('search');
		$status = $request->string('status');

		$roles = Role::query()
			->withCount('users')
			->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
			// Se adicionaste a coluna 'status' em roles, o filtro abaixo funciona:
			->when($status, fn($q) => $q->where('status', $status))
			->orderBy('name')
			->paginate(10)
			->withQueryString();

		$allPermissions = Permission::orderBy('name')->get(['id', 'name']);

		return Inertia::render('Access/RolesIndex', [
			'roles' => $roles,
			'permissions' => $allPermissions,
			'filters' => [
				'search' => $search,
				'status' => $status,
			],
			'permissionMatrix' => [
				'Menu A' => ['menu_a.create', 'menu_a.read', 'menu_a.update', 'menu_a.delete'],
				'Menu B' => ['menu_b.create', 'menu_b.read', 'menu_b.update', 'menu_b.delete'],
			],
		]);
	}

	public function store(StoreRoleRequest $request)
	{
		$data = $request->validated();

		// Se não criaste a coluna 'status' em roles, remove do create/update.
		$role = Role::create([
			'name' => $data['name'],
			'guard_name' => 'web',
			// 'status' => $data['status'],
		]);

		$role->syncPermissions($data['permissions'] ?? []);
		return back()->with('success', 'Grupo criado com sucesso.');
	}

	public function update(UpdateRoleRequest $request, Role $role)
	{
		$data = $request->validated();

		$role->update([
			'name' => $data['name'],
			// 'status' => $data['status'],
		]);

		$role->syncPermissions($data['permissions'] ?? []);
		return back()->with('success', 'Grupo atualizado com sucesso.');
	}

	public function toggleStatus(Role $role)
	{
		// Só disponível se tiveres a coluna 'status' em roles:
		if (! $role->getAttribute('status')) {
			return back()->withErrors('A coluna "status" não existe na tabela roles.');
		}

		$role->status = $role->status === 'active' ? 'inactive' : 'active';
		$role->save();
		return back()->with('success', 'Estado do grupo atualizado.');
	}

	public function destroy(Role $role)
	{
		if ($role->users()->count() > 0) {
			return back()->withErrors('Não é possível remover um grupo com utilizadores associados.');
		}
		$role->delete();
		return back()->with('success', 'Grupo removido.');
	}
}
