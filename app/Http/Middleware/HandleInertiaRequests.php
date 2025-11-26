<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Tenant;

class HandleInertiaRequests extends Middleware
{
	protected $rootView = 'app';

	public function share(Request $request): array
	{
		\Log::debug('=== HANDLE INERTIA REQUESTS ===', [
			'tenancy_initialized' => tenancy()->initialized,
			'session_tenant' => session('tenant_id'),
			'has_user' => !!$request->user()
		]);

		$currentTenant = null;

		if (tenancy()->initialized) {
			$currentTenant = tenant()->only(['id', 'name', 'slug']);
			\Log::debug('Tenancy is initialized', ['current_tenant' => $currentTenant]);
		} else {
			\Log::debug('Tenancy is NOT initialized');

			// Fallback: tenta pegar da session
			$tenantId = session('tenant_id') ?? $request->user()?->last_tenant_id;
			if ($tenantId) {
				$tenant = \Stancl\Tenancy\Database\Models\Tenant::find($tenantId);
				$currentTenant = $tenant ? $tenant->only(['id', 'name', 'slug']) : null;
				\Log::debug('Fallback tenant from session', ['current_tenant' => $currentTenant]);
			}
		}

		return array_merge(parent::share($request), [
			'csrf_token' => csrf_token(),

			'auth' => [
				'user' => $request->user()?->only(['id', 'name', 'email']),
				'current_tenant' => $currentTenant, // ← USA A VARIÁVEL QUE DEFINIMOS
				'tenants' => $request->user()?->tenants()->get(['tenants.id', 'tenants.name', 'tenants.slug'])->map(function ($t) {
					$t->is_owner = $t->pivot->role === 'owner';
					return $t;
				})->values() ?? [],
			],

			// ... resto do seu código
		]);
	}
}
