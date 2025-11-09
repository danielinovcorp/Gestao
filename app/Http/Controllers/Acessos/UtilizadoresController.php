<?php

namespace App\Http\Controllers\Acessos;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UtilizadoresController extends Controller
{
	public function index(Request $request)
	{
		$search = $request->input('search');
		$status = $request->input('status');

		$users = User::query()
			->with('roles')
			->when($search, fn($q) => $q->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('email', 'like', "%{$search}%")
					->orWhere('phone', 'like', "%{$search}%");
			}))
			->when($status, fn($q) => $q->where('status', $status))
			->paginate(10)
			->withQueryString();

		$roles = Role::where('status', 'active')->pluck('name', 'id');

		return Inertia::render('Acessos/Utilizadores/Index', [
			'users' => $users,
			'roles' => $roles,
			'filters' => ['search' => $search, 'status' => $status],
		]);
	}

	public function store(Request $request)
	{
		$validated = $request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|email|unique:users,email',
			'phone' => 'nullable|string|max:20',
			'role' => 'required|string|exists:roles,name',
			'password' => 'required|string|min:6',
			'status' => 'required|in:active,inactive',
		]);

		$user = User::create([
			'name' => $validated['name'],
			'email' => $validated['email'],
			'phone' => $validated['phone'],
			'password' => bcrypt($validated['password']),
			'status' => $validated['status'],
		]);

		$user->assignRole($validated['role']);

		return back()->with('success', 'Utilizador criado com sucesso.');
	}

	public function update(Request $request, User $user)
	{
		$validated = $request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|email|unique:users,email,' . $user->id,
			'phone' => 'nullable|string|max:20',
			'role' => 'required|string|exists:roles,name',
			'status' => 'required|in:active,inactive',
		]);

		$user->update([
			'name' => $validated['name'],
			'email' => $validated['email'],
			'phone' => $validated['phone'],
			'status' => $validated['status'],
		]);

		$user->syncRoles([$validated['role']]);

		return back()->with('success', 'Utilizador atualizado.');
	}

	public function destroy(User $user)
	{
		$user->delete();
		return back();
	}

	public function toggleStatus(User $user)
	{
		$user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
		return back();
	}
}
