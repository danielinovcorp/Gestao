<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
	public function __construct()
	{
		$this->middleware(['auth', 'active']);
		// ✅ trocado para can:
		$this->middleware('can:access.users.view')->only(['index']);
		$this->middleware('can:access.users.create')->only(['store']);
		$this->middleware('can:access.users.update')->only(['update', 'toggleStatus']);
		$this->middleware('can:access.users.delete')->only(['destroy']);
	}

	public function index(Request $request)
	{
		$search = $request->string('search');
		$status = $request->string('status');

		$users = User::query()
			->with('roles:id,name')
			->when($search, fn($q) => $q->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('email', 'like', "%{$search}%")
					->orWhere('phone', 'like', "%{$search}%");
			}))
			->when($status, fn($q) => $q->where('status', $status))
			->orderBy('name')
			->paginate(10)
			->withQueryString();

		$roles = Role::orderBy('name')->get(['id', 'name']);

		return Inertia::render('Access/UsersIndex', [
			'users' => $users,
			'roles' => $roles,
			'filters' => [
				'search' => $search,
				'status' => $status,
			],
		]);
	}

	public function store(StoreUserRequest $request)
	{
		$data = $request->validated();

		$user = User::create([
			'name'     => $data['name'],
			'email'    => $data['email'],
			'phone'    => $data['phone'] ?? null,
			'status'   => $data['status'],
			'password' => Hash::make($data['password']),
		]);

		$user->syncRoles([$data['role']]);

		return back()->with('success', 'Utilizador criado com sucesso.');
	}

	public function update(UpdateUserRequest $request, User $user)
	{
		$data = $request->validated();

		$user->fill([
			'name'   => $data['name'],
			'email'  => $data['email'],
			'phone'  => $data['phone'] ?? null,
			'status' => $data['status'],
		]);

		if (!empty($data['password'])) {
			$user->password = Hash::make($data['password']);
		}

		$user->save();
		$user->syncRoles([$data['role']]);

		return back()->with('success', 'Utilizador atualizado com sucesso.');
	}

	public function toggleStatus(User $user)
	{
		$user->status = $user->status === 'active' ? 'inactive' : 'active';
		$user->save();
		return back()->with('success', 'Estado atualizado.');
	}

	public function destroy(User $user)
	{
		$user->delete();
		return back()->with('success', 'Utilizador removido.');
	}
}
