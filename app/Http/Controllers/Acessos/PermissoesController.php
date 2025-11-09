<?php

namespace App\Http\Controllers\Acessos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissoesController extends Controller
{
	public function index(Request $request)
	{
		$search = $request->input('search');
		$status = $request->input('status');

		$roles = Role::query()
			->withCount('users')
			->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
			->when($status, fn($q) => $q->where('status', $status))
			->paginate(10)
			->withQueryString();

		$permissions = Permission::all()->pluck('name');
		$permissionMatrix = $this->buildPermissionMatrix($permissions);

		return Inertia::render('Acessos/Permissoes/Index', [
			'roles' => $roles,
			'permissionMatrix' => $permissionMatrix,
			'filters' => ['search' => $search, 'status' => $status],
		]);
	}

	private function buildPermissionMatrix($permissions)
	{
		$matrix = [];

		foreach ($permissions as $perm) {
			$parts = explode('.', $perm, 2);

			// Se não tiver ponto, pula ou coloca em "Outros"
			if (count($parts) < 2) {
				$label = 'Outros';
				$action = $perm;
			} else {
				[$menu, $action] = $parts;
				$label = ucfirst(str_replace('_', ' ', $menu));
			}

			$matrix[$label][] = $perm;
		}

		return $matrix;
	}

	public function store(Request $request)
	{
		$validated = $request->validate([
			'name' => 'required|string|unique:roles,name',
			'status' => 'required|in:active,inactive',
			'permissions' => 'required|array',
			'permissions.*' => 'exists:permissions,name',
		]);

		$role = Role::create([
			'name' => $validated['name'],
			'status' => $validated['status'],
			'guard_name' => 'web',
		]);

		$role->syncPermissions($validated['permissions']);

		return back()->with('success', 'Grupo criado.');
	}

	public function update(Request $request, Role $role)
	{
		$validated = $request->validate([
			'name' => 'required|string|unique:roles,name,' . $role->id,
			'status' => 'required|in:active,inactive',
			'permissions' => 'required|array',
			'permissions.*' => 'exists:permissions,name',
		]);

		$role->update([
			'name' => $validated['name'],
			'status' => $validated['status'],
		]);

		$role->syncPermissions($validated['permissions']);

		return back()->with('success', 'Grupo atualizado.');
	}

	public function destroy(Role $role)
	{
		$role->delete();
		return back();
	}

	public function toggleStatus(Role $role)
	{
		$role->update(['status' => $role->status === 'active' ? 'inactive' : 'active']);
		return back();
	}
}
