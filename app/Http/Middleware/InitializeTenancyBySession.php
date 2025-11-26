<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;

class InitializeTenancyBySession
{
	public function handle(Request $request, Closure $next)
	{
		if (!Auth::check()) {
			return $next($request);
		}

		// DEBUG - remover depois
		\Log::debug('=== TENANCY MIDDLEWARE START ===', [
			'user_id' => Auth::id(),
			'session_tenant' => session('tenant_id'),
			'last_tenant' => Auth::user()->last_tenant_id,
			'tenancy_initialized_before' => tenancy()->initialized
		]);

		$tenantId = session('tenant_id') ?? Auth::user()->last_tenant_id;

		// Se não tem tenant ID, tenta pegar o primeiro tenant do usuário
		if (!$tenantId) {
			$firstTenant = Auth::user()->tenants()->first();
			$tenantId = $firstTenant?->id;

			if ($tenantId) {
				// Salva como último tenant para usar da próxima vez
				Auth::user()->update(['last_tenant_id' => $tenantId]);
				session(['tenant_id' => $tenantId]);
			}
		}

		if ($tenantId) {
			$tenant = Tenant::find($tenantId);

			if ($tenant && Auth::user()->tenants()->where('tenant_id', $tenantId)->exists()) {
				\Log::debug('Initializing tenant', [
					'tenant_id' => $tenantId,
					'tenant_name' => $tenant->name
				]);

				// ✅ ABORDAGEM CORRETA - Use o Facade
				try {
					Tenancy::initialize($tenant);
					\Log::debug('Tenancy initialized successfully', [
						'current_tenant' => tenant()->id,
						'tenancy_initialized_after' => tenancy()->initialized
					]);
				} catch (\Exception $e) {
					\Log::error('Failed to initialize tenancy', [
						'error' => $e->getMessage(),
						'tenant_id' => $tenantId
					]);
				}
			} else {
				\Log::warning('Tenant not found or user not authorized', [
					'tenant_id' => $tenantId,
					'tenant_exists' => !!$tenant,
					'user_has_access' => $tenant ? Auth::user()->tenants()->where('tenant_id', $tenantId)->exists() : false
				]);
			}
		} else {
			\Log::debug('No tenant ID found for user');
		}

		\Log::debug('=== TENANCY MIDDLEWARE END ===', [
			'tenancy_initialized' => tenancy()->initialized,
			'current_tenant' => tenancy()->initialized ? tenant()->id : null
		]);

		return $next($request);
	}
}
