<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		//
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		// Prefetch do Vite (mantém o que já tinhas)
		Vite::prefetch(concurrency: 3);

		// 🔒 Força HTTPS apenas em produção
		if (app()->environment('production')) {
			URL::forceScheme('https');
		}

		Activity::saving(function (Activity $activity) {
			try {
				$req = request();
				$props = $activity->properties?->toArray() ?? [];
				$props['ip']         = $props['ip']         ?? $req->ip();
				$props['user_agent'] = $props['user_agent'] ?? $req->userAgent();
				$props['route']      = $props['route']      ?? optional($req->route())->getName();
				$props['url']        = $props['url']        ?? $req->fullUrl();
				$activity->properties = $props;
			} catch (\Throwable $e) {
				// ignora silenciosamente
			}
		});
	}
}
