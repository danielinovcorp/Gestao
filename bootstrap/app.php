<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
	->withProviders([
		App\Providers\FortifyServiceProvider::class,
	])
	->withRouting(
		web: __DIR__ . '/../routes/web.php',
		api: __DIR__ . '/../routes/api.php',
		commands: __DIR__ . '/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
		// grupos padrão
		$middleware->web(append: [
			\App\Http\Middleware\HandleInertiaRequests::class,
			\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
		]);
		$middleware->api();

		// 🔹 TUAS ALIASES
		$middleware->alias([
			// teu middleware
			'active' => \App\Http\Middleware\EnsureUserIsActive::class,

			// Spatie Permission
			'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
			'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
			'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
		]);
	})
	->withExceptions(function (Exceptions $exceptions): void {
		//
	})
	->create();
