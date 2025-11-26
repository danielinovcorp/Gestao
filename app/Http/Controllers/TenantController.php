<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TenantController extends Controller
{
	public function create()
	{
		return Inertia::render('Tenants/Create');
	}

	public function store(Request $request)
	{
		$request->validate(['name' => 'required|string|max:255']);

		$tenant = Tenant::create([
			'owner_id' => auth()->id(),
			'name'     => $request->name,
			'slug'     => Str::slug($request->name),
		]);

		$tenant->users()->attach(auth()->id(), ['role' => 'owner']);

		session(['tenant_id' => $tenant->id]);
		auth()->user()->update(['last_tenant_id' => $tenant->id]);

		return redirect()->route('dashboard')
			->with('success', 'Empresa criada com sucesso!');
	}

	public function switch(Tenant $tenant)
	{
		if (!auth()->user()->tenants()->where('tenant_id', $tenant->id)->exists()) {
			abort(403);
		}

		\Log::debug('Switching tenant', [
			'from' => session('tenant_id'),
			'to' => $tenant->id,
			'user' => auth()->id()
		]);

		session(['tenant_id' => $tenant->id]);
		auth()->user()->update(['last_tenant_id' => $tenant->id]);

		return redirect()->route('dashboard')
			->with('success', 'Empresa alterada com sucesso!');
	}

	public function onboarding()
	{
		// Só mostra o wizard se o usuário ainda não tem tenant ativo
		if (auth()->user()->tenants()->exists()) {
			return redirect()->route('dashboard');
		}

		return Inertia::render('Tenants/Onboarding');
	}

	public function storeOnboarding(Request $request)
	{
		$request->validate([
			'name'        => 'required|string|max:100',
			'primary_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
			'logo'        => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
			'invites.*.email' => 'nullable|email',
		]);

		// 1. Criar tenant
		$tenant = Tenant::create([
			'id'       => (string) \Illuminate\Support\Str::ulid(),
			'owner_id' => auth()->id(),
			'name'     => $request->name,
			'slug'     => \Illuminate\Support\Str::slug($request->name),
			'data'     => [
				'primary_color' => $request->primary_color,
			],
		]);

		// 2. Logo
		if ($request->hasFile('logo')) {
			$path = $request->file('logo')->store("tenants/{$tenant->id}", 'private');
			$tenant->update(['data->logo_path' => $path]);
		}

		// 3. Adicionar owner
		$tenant->users()->attach(auth()->id(), ['role' => 'owner']);

		// 4. Convites
		if ($request->filled('invites')) {
			foreach ($request->invites as $invite) {
				if (!empty($invite['email'])) {
					// Aqui podes disparar email com link mágico ou só guardar na tabela invites
					// Por agora guardamos numa tabela simples (cria depois se quiseres)
				}
			}
		}

		// 5. Ativar tenant
		session(['tenant_id' => $tenant->id]);
		auth()->user()->update(['last_tenant_id' => $tenant->id]);

		return redirect()->route('dashboard')
			->with('success', "Bem-vindo à {$tenant->name}! Tudo pronto");
	}
}
